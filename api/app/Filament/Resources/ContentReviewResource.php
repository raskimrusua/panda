<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContentReviewResource\Pages;
use App\Models\ContentReview;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;

/**
 * Agronomist content review track.
 *
 * Silas (or any agronomist) drafts an edit, submits for review. An ops
 * superuser approves or requests changes. Approval (later PR) triggers
 * the export job that writes resources/content/<type>/<slug>.json and
 * commits via the GitHub API.
 *
 * This panel handles the workflow only. The structured per-crop content
 * editor (timeline activities, input rows, varieties) is a richer surface
 * landing in its own PR.
 */
class ContentReviewResource extends Resource
{
    protected static ?string $model = ContentReview::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Content';

    protected static ?int $navigationSort = 20;

    public static function getNavigationBadge(): ?string
    {
        $pending = ContentReview::pending()->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('target_type')
                    ->options([
                        ContentReview::TARGET_CROP => 'Crop',
                        ContentReview::TARGET_DISEASE => 'Disease',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('target_slug')
                    ->required()
                    ->maxLength(64)
                    ->helperText('e.g. tomato, early-blight. Must match the JSON file slug.'),
                Forms\Components\Select::make('status')
                    ->options([
                        ContentReview::STATUS_DRAFT => 'Draft',
                        ContentReview::STATUS_SUBMITTED => 'Submitted',
                        ContentReview::STATUS_APPROVED => 'Approved',
                        ContentReview::STATUS_CHANGES_REQUESTED => 'Changes requested',
                    ])
                    ->required()
                    ->default(ContentReview::STATUS_DRAFT),
                Forms\Components\Textarea::make('reviewer_notes')
                    ->rows(4)
                    ->maxLength(2000)
                    ->columnSpanFull(),
                Forms\Components\KeyValue::make('content_payload')
                    ->columnSpanFull()
                    ->helperText('Raw payload — the structured editor lands in a later PR.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('target_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'crop' ? 'success' : 'warning'),
                Tables\Columns\TextColumn::make('target_slug')
                    ->label('Slug')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        ContentReview::STATUS_DRAFT => 'gray',
                        ContentReview::STATUS_SUBMITTED => 'warning',
                        ContentReview::STATUS_APPROVED => 'success',
                        ContentReview::STATUS_CHANGES_REQUESTED => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('submitter.name')
                    ->label('Submitted by')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('reviewer.name')
                    ->label('Reviewer')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('submitted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('decided_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        ContentReview::STATUS_DRAFT => 'Draft',
                        ContentReview::STATUS_SUBMITTED => 'Submitted',
                        ContentReview::STATUS_APPROVED => 'Approved',
                        ContentReview::STATUS_CHANGES_REQUESTED => 'Changes requested',
                    ]),
                Tables\Filters\SelectFilter::make('target_type')
                    ->options([
                        ContentReview::TARGET_CROP => 'Crop',
                        ContentReview::TARGET_DISEASE => 'Disease',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('approve')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (ContentReview $record): bool => $record->status === ContentReview::STATUS_SUBMITTED)
                    ->requiresConfirmation()
                    ->action(function (ContentReview $record): void {
                        $record->update([
                            'status' => ContentReview::STATUS_APPROVED,
                            'reviewer_id' => auth()->id(),
                            'decided_at' => Carbon::now(),
                        ]);
                        // TODO: dispatch ExportContentJob (later PR — wires the
                        // GitHub commit of the regenerated content JSON).
                    }),
                Tables\Actions\Action::make('requestChanges')
                    ->label('Request changes')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->visible(fn (ContentReview $record): bool => $record->status === ContentReview::STATUS_SUBMITTED)
                    ->form([
                        Forms\Components\Textarea::make('reviewer_notes')
                            ->required()
                            ->rows(4)
                            ->maxLength(2000),
                    ])
                    ->action(function (ContentReview $record, array $data): void {
                        $record->update([
                            'status' => ContentReview::STATUS_CHANGES_REQUESTED,
                            'reviewer_id' => auth()->id(),
                            'reviewer_notes' => $data['reviewer_notes'],
                            'decided_at' => Carbon::now(),
                        ]);
                    }),
            ]);
    }

    /** @return array<string, PageRegistration> */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContentReviews::route('/'),
            'create' => Pages\CreateContentReview::route('/create'),
            'edit' => Pages\EditContentReview::route('/{record}/edit'),
        ];
    }
}
