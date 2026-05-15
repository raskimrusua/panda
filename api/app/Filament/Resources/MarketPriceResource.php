<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MarketPriceResource\Pages;
use App\Models\Crop;
use App\Models\MarketPrice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Filament admin for the observed market-price catalogue. Shared
 * (not tenant-scoped) — every farm sees the same prices.
 *
 * The bulk of rows arrive via AMIS/CSV import (~1500 seed rows). This
 * panel is for ad-hoc curation: a field agent reports a single price,
 * or an admin patches a fat-fingered import. The model is append-only
 * in spirit — wrong rows are corrected by adding a new one, not by
 * editing in place. Edit is still permitted (an admin owns the source
 * field) but discouraged via the helper text.
 */
class MarketPriceResource extends Resource
{
    protected static ?string $model = MarketPrice::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Catalogue';

    protected static ?int $navigationSort = 30;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('What was observed')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('crop_id')
                            ->relationship('crop', 'name_en')
                            ->searchable()
                            ->required(),
                        Forms\Components\DatePicker::make('observed_at')
                            ->required()
                            ->maxDate(now()),
                        Forms\Components\TextInput::make('market_name')
                            ->required()
                            ->maxLength(160),
                        Forms\Components\TextInput::make('county')
                            ->required()
                            ->maxLength(64),
                        Forms\Components\Select::make('grade')
                            ->options([
                                'a' => 'A (premium)',
                                'b' => 'B (standard)',
                                'c' => 'C (rejects)',
                            ]),
                        Forms\Components\TextInput::make('price_per_kg_kes')
                            ->label('Price per kg (KES)')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01),
                    ]),

                Forms\Components\Section::make('Provenance')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('source')
                            ->required()
                            ->options([
                                MarketPrice::SOURCE_ADMIN_CSV => 'Admin CSV import',
                                MarketPrice::SOURCE_AMIS_KENYA => 'AMIS Kenya',
                                MarketPrice::SOURCE_FIELD_AGENT => 'Field agent',
                            ]),
                        Forms\Components\Textarea::make('notes')
                            ->rows(2)
                            ->maxLength(2000)
                            ->helperText('Wrong row? Prefer inserting a corrected entry rather than editing — this table is append-only in spirit.'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('observed_at')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('crop.name_en')
                    ->label('Crop')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('market_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('county')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('grade')
                    ->badge()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('price_per_kg_kes')
                    ->label('KES / kg')
                    ->money('KES')
                    ->sortable(),
                Tables\Columns\TextColumn::make('source')
                    ->badge()
                    ->toggleable(),
            ])
            ->defaultSort('observed_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('crop_id')
                    ->label('Crop')
                    ->options(
                        fn () => Crop::query()->orderBy('name_en')->pluck('name_en', 'id')->all()
                    )
                    ->searchable(),
                Tables\Filters\SelectFilter::make('county')
                    ->options(
                        fn () => MarketPrice::query()->distinct()->orderBy('county')->pluck('county', 'county')->all()
                    ),
                Tables\Filters\SelectFilter::make('source')
                    ->options([
                        MarketPrice::SOURCE_ADMIN_CSV => 'Admin CSV',
                        MarketPrice::SOURCE_AMIS_KENYA => 'AMIS Kenya',
                        MarketPrice::SOURCE_FIELD_AGENT => 'Field agent',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    /** @return array<string, PageRegistration> */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMarketPrices::route('/'),
            'create' => Pages\CreateMarketPrice::route('/create'),
            'edit' => Pages\EditMarketPrice::route('/{record}/edit'),
        ];
    }
}
