<?php

namespace App\Filament\Resources\WhatsAppMessages;

use App\Filament\Resources\WhatsAppMessages\Pages\CreateWhatsAppMessage;
use App\Filament\Resources\WhatsAppMessages\Pages\EditWhatsAppMessage;
use App\Filament\Resources\WhatsAppMessages\Pages\ListWhatsAppMessages;
use App\Filament\Resources\WhatsAppMessages\Pages\ViewWhatsAppMessage;
use App\Filament\Resources\WhatsAppMessages\Schemas\WhatsAppMessageForm;
use App\Filament\Resources\WhatsAppMessages\Schemas\WhatsAppMessageInfolist;
use App\Filament\Resources\WhatsAppMessages\Tables\WhatsAppMessagesTable;
use App\Models\WhatsAppMessage;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class WhatsAppMessageResource extends Resource
{
    protected static ?string $model = WhatsAppMessage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?string $navigationLabel = 'Messages';

    protected static ?int $navigationSort = 20;

    public static function getNavigationGroup(): string
    {
        return 'WhatsApp';
    }

    public static function form(Schema $schema): Schema
    {
        return WhatsAppMessageForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return WhatsAppMessageInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WhatsAppMessagesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWhatsAppMessages::route('/'),
            'create' => CreateWhatsAppMessage::route('/create'),
            'view' => ViewWhatsAppMessage::route('/{record}'),
            'edit' => EditWhatsAppMessage::route('/{record}/edit'),
        ];
    }
}
