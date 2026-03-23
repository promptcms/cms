<?php

namespace App\Filament\Resources;

use App\Enums\NodeStatus;
use App\Enums\NodeType;
use App\Filament\Resources\PageResource\Pages;
use App\Models\Node;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PageResource extends Resource
{
    protected static ?string $model = Node::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Seiten';

    protected static ?string $modelLabel = 'Seite';

    protected static ?string $pluralModelLabel = 'Seiten';

    protected static ?int $navigationSort = 1;

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->ofType(NodeType::Page)->orderBy('sort_order'))
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Titel')
                    ->searchable(),
                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => NodeStatus::Published->value,
                        'warning' => NodeStatus::Draft->value,
                    ]),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Aktualisiert')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->actions([
                Action::make('preview')
                    ->label('Vorschau')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Node $record) => $record->slug === 'home' ? '/' : '/'.$record->slug)
                    ->openUrlInNewTab(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPages::route('/'),
        ];
    }
}
