<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DealerResource\Pages;
use App\Models\Dealer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Filament admin for the agro-input dealer directory. Shared catalogue
 * (not tenant-scoped) — every farm sees the same dealer list when they
 * open the map.
 *
 * Bulk import (~30 seed dealers) happens via tinker + DealerSeeder.
 * This panel is for ongoing curation: fixing a phone number, marking a
 * dealer inactive after a complaint, toggling PCPB certification when
 * the certificate is renewed/lapsed.
 */
class DealerResource extends Resource
{
    protected static ?string $model = Dealer::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationGroup = 'Catalogue';

    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Identity')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(160),
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(160)
                            ->disabled(fn (?Dealer $record): bool => $record !== null)
                            ->helperText('Lowercase, URL-safe. Locked after creation.'),
                    ]),

                Forms\Components\Section::make('Location')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('county')
                            ->required()
                            ->maxLength(64),
                        Forms\Components\TextInput::make('sub_county')
                            ->maxLength(64),
                        Forms\Components\TextInput::make('town')
                            ->maxLength(120),
                        Forms\Components\TextInput::make('gps_lat')
                            ->label('GPS latitude')
                            ->numeric()
                            ->step(0.000001)
                            ->minValue(-5)
                            ->maxValue(5)
                            ->helperText('Kenya is roughly 5°S to 5°N.'),
                        Forms\Components\TextInput::make('gps_lng')
                            ->label('GPS longitude')
                            ->numeric()
                            ->step(0.000001)
                            ->minValue(33)
                            ->maxValue(42),
                    ]),

                Forms\Components\Section::make('Contact')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(32),
                        Forms\Components\TextInput::make('whatsapp')
                            ->tel()
                            ->maxLength(32),
                        Forms\Components\TextInput::make('website')
                            ->url()
                            ->maxLength(255),
                    ]),

                Forms\Components\Section::make('Inventory + status')
                    ->columns(2)
                    ->schema([
                        Forms\Components\CheckboxList::make('stocks')
                            ->options([
                                Dealer::STOCK_SEED => 'Seed',
                                Dealer::STOCK_FERTILISER => 'Fertiliser',
                                Dealer::STOCK_CHEMICAL => 'Chemical / pesticide',
                                Dealer::STOCK_EQUIPMENT => 'Equipment',
                            ])
                            ->required()
                            ->bulkToggleable(),
                        Forms\Components\Toggle::make('is_pcpb_certified')
                            ->label('PCPB-certified')
                            ->helperText('Pest Control Products Board — required for legal chemical sales.'),
                        Forms\Components\Toggle::make('is_active')
                            ->required()
                            ->default(true)
                            ->helperText('Inactive dealers are hidden from the farmer map.'),
                    ]),

                Forms\Components\Section::make('Notes')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->rows(3)
                            ->maxLength(2000)
                            ->helperText('Internal — never shown to farmers.'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('county')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('town')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_pcpb_certified')
                    ->label('PCPB')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active only')
                    ->default(true),
                Tables\Filters\TernaryFilter::make('is_pcpb_certified')
                    ->label('PCPB-certified'),
                Tables\Filters\SelectFilter::make('county')
                    ->options(
                        fn () => Dealer::query()->distinct()->orderBy('county')->pluck('county', 'county')->all()
                    ),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    /** @return array<string, PageRegistration> */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDealers::route('/'),
            'create' => Pages\CreateDealer::route('/create'),
            'edit' => Pages\EditDealer::route('/{record}/edit'),
        ];
    }
}
