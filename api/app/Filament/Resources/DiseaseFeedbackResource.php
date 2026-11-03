<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DiseaseFeedbackResource\Pages;
use App\Models\DiseaseFeedback;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Phase B — retrain-queue triage surface. Ops reviews farmer
 * corrections (esp. incorrect verdicts), marks rows reviewed when pulled
 * into a training batch, and exports via `panda:export-feedback`.
 *
 * Read-focused: feedback is farmer-owned evidence; the only write here is
 * the reviewed stamp.
 */
class DiseaseFeedbackResource extends Resource
{
    protected static ?string $model = DiseaseFeedback::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationGroup = 'Disease';

    protected static ?int $navigationSort = 30;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('verdict')
                    ->badge()
                    ->color(fn (string $state): string => $state === DiseaseFeedback::VERDICT_CORRECT ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('correction_text')
                    ->label('Correction')
                    ->wrap()
                    ->limit(80)
                    ->placeholder('—')
                    ->searchable(),
                Tables\Columns\TextColumn::make('correction_key')
                    ->label('Key')
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('diseaseDetection.top_diagnosis')
                    ->label('Diagnosed')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('diseaseDetection.provider')
                    ->label('Provider')
                    ->badge()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('diseaseDetection.image_path')
                    ->label('Image path')
                    ->copyable()
                    ->wrap()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Farm')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('reviewed_at')
                    ->label('Reviewed')
                    ->dateTime()
                    ->placeholder('In queue')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('verdict')
                    ->options([
                        DiseaseFeedback::VERDICT_CORRECT => 'Correct',
                        DiseaseFeedback::VERDICT_INCORRECT => 'Incorrect',
                    ]),
                Tables\Filters\TernaryFilter::make('reviewed')
                    ->label('Reviewed')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('reviewed_at'),
                        false: fn ($query) => $query->whereNull('reviewed_at'),
                    ),
            ])
            ->actions([
                Tables\Actions\Action::make('markReviewed')
                    ->label('Mark reviewed')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn (DiseaseFeedback $record): bool => $record->reviewed_at === null)
                    ->action(fn (DiseaseFeedback $record) => $record->markReviewed(auth()->user()))
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('markReviewedBulk')
                    ->label('Mark reviewed')
                    ->icon('heroicon-o-check-circle')
                    ->action(function ($records): void {
                        $user = auth()->user();
                        foreach ($records as $record) {
                            $record->markReviewed($user);
                        }
                    })
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDiseaseFeedback::route('/'),
        ];
    }
}
