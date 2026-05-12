<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CropResource\Pages;
use App\Models\Crop;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Filament admin for Crop catalogue metadata.
 *
 * IMPORTANT: the JSON content file at resources/content/crops/<slug>.json is
 * the source of truth for the AGRONOMIC content (timeline, inputs, varieties).
 * This panel only edits the catalogue metadata row (display name, category,
 * image, active toggle). Agronomic content edits go through the ContentReview
 * track + the per-crop structured editor (deferred to a later PR).
 */
class CropResource extends Resource
{
    protected static ?string $model = Crop::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Content';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->maxLength(64)
                    ->disabled(fn (?Crop $record): bool => $record !== null)
                    ->helperText('URL-safe lowercase identifier — locked after creation.'),
                Forms\Components\TextInput::make('name_en')
                    ->label('Name (English)')
                    ->required()
                    ->maxLength(120),
                Forms\Components\TextInput::make('name_sw')
                    ->label('Name (Swahili)')
                    ->required()
                    ->maxLength(120),
                Forms\Components\Select::make('category')
                    ->required()
                    ->options([
                        'leafy' => 'Leafy',
                        'fruiting' => 'Fruiting',
                        'root' => 'Root',
                        'bulb' => 'Bulb',
                        'legume' => 'Legume',
                        'fruit_tree' => 'Fruit tree',
                    ]),
                Forms\Components\Select::make('harvest_type')
                    ->required()
                    ->options([
                        'single' => 'Single (one harvest, e.g. cabbage)',
                        'multi' => 'Multi-pick (continuous, e.g. tomato)',
                    ]),
                Forms\Components\TextInput::make('image_url')
                    ->url()
                    ->maxLength(500)
                    ->helperText('Public CDN URL. Upload via R2 outside the panel.'),
                Forms\Components\TextInput::make('jica_manual_ref')
                    ->label('JICA SHEP PLUS manual page')
                    ->maxLength(120),
                Forms\Components\TextInput::make('phase_added')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(3)
                    ->default(1)
                    ->helperText('Phase 1 = JAICA MVP 5 crops; Phase 2/3 = later batches.'),
                Forms\Components\Toggle::make('is_active')
                    ->required()
                    ->default(true)
                    ->helperText('Inactive crops are hidden from the public catalogue.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('slug')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name_en')
                    ->label('English')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name_sw')
                    ->label('Swahili')
                    ->searchable(),
                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('harvest_type')
                    ->badge(),
                Tables\Columns\TextColumn::make('phase_added')
                    ->label('Phase')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('phase_added')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active only')
                    ->default(true),
                Tables\Filters\SelectFilter::make('phase_added')
                    ->options([1 => 'Phase 1', 2 => 'Phase 2', 3 => 'Phase 3']),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    /** @return array<string, PageRegistration> */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCrops::route('/'),
            'create' => Pages\CreateCrop::route('/create'),
            'edit' => Pages\EditCrop::route('/{record}/edit'),
        ];
    }
}
