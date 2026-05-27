<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientResource\Pages;
use App\Filament\Resources\ClientResource\RelationManagers;
use App\Models\Client;
use App\Services\InvoiceService;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\ExportBulkAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;
    protected static ?string $navigationGroup = 'Collections';
    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Client Information')->schema([
                TextInput::make('client_number')->disabled()->dehydrated(false),
                TextInput::make('name')->required()->maxLength(255),
                TextInput::make('phone')->tel()->nullable(),
                TextInput::make('email')->email()->nullable(),
            ])->columns(2),

            Section::make('Classification & Zone')->schema([
                Select::make('client_type_id')
                    ->relationship('clientType', 'name')
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(fn($state, callable $set) =>
                        $set('monthly_fee', ClientType::find($state)?->default_monthly_fee)),
                Select::make('zone_id')
                    ->relationship('zone', 'name')
                    ->required(),
                TextInput::make('monthly_fee')
                    ->numeric()->prefix('TZS')->required(),
                Select::make('status')
                    ->options(['active'=>'Active','inactive'=>'Inactive','suspended'=>'Suspended'])
                    ->default('active'),
            ])->columns(2),

            Section::make('Address & Notes')->schema([
                Textarea::make('address')->rows(2),
                Textarea::make('notes')->rows(2),
                DatePicker::make('contract_start_date'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('client_number')->searchable()->sortable(),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('zone.name')->badge(),
                TextColumn::make('clientType.name')->badge()->color('success'),
                TextColumn::make('monthly_fee')->money('TZS')->sortable(),
                TextColumn::make('outstanding_balance')
                    ->money('TZS')
                    ->color(fn($record) => $record->outstanding_balance > 0 ? 'danger' : 'success')
                    ->label('Outstanding'),
                BadgeColumn::make('status')
                    ->colors(['success'=>'active','warning'=>'inactive','danger'=>'suspended']),
            ])
            ->filters([
                SelectFilter::make('zone')->relationship('zone', 'name'),
                SelectFilter::make('client_type')->relationship('clientType', 'name'),
                SelectFilter::make('status')->options(['active'=>'Active','inactive'=>'Inactive']),
                Filter::make('has_debt')
                    ->query(fn($query) => $query->whereHas('debts', fn($q) => $q->where('status','active')))
                    ->label('Has Active Debt'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Action::make('payment_history')
                    ->icon('heroicon-o-document-text')
                    ->url(fn($record) => route('filament.admin.resources.clients.payments', $record)),
            ])
            ->bulkActions([
                BulkAction::make('generate_invoices')
                    ->label('Generate Invoices')
                    ->action(fn($records) => app(InvoiceService::class)->generateForClients($records))
                    ->requiresConfirmation(),
                ExportBulkAction::make(),
            ]);
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
            'index' => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'edit' => Pages\EditClient::route('/{record}/edit'),
        ];
    }
}
