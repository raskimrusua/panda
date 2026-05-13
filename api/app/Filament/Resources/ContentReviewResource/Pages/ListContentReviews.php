<?php

namespace App\Filament\Resources\ContentReviewResource\Pages;

use App\Filament\Resources\ContentReviewResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListContentReviews extends ListRecords
{
    protected static string $resource = ContentReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
