<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConversationResource\Pages;
use App\Models\Conversation;
use App\Models\User;
use App\Services\MessagingService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use App\Traits\VisibleToRolesTreasurer;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Section;

class ConversationResource extends Resource
{
    use VisibleToRolesTreasurer;
    
    protected static ?string $model = Conversation::class;
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationLabel = 'Mensajería';
    protected static ?string $navigationGroup = 'Comunicación';

    public static function getPluralModelLabel(): string
    {
        return 'Conversaciones'; // Texto personalizado para el título principal
    }

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();
        
        if (!$user) {
            return null;
        }
        
        // Contar notificaciones de mensajes no leídas
        $count = $user->unreadNotifications()
            ->where('type', 'App\\Notifications\\NewMessageNotification')
            ->count();
        
        // DEBUG temporal
        \Illuminate\Support\Facades\Log::info('Badge: Conteo actual de notificaciones: ' . $count);
        
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Información básica en una sola sección
                Section::make('Nueva Conversación')
                    ->description('Complete la información básica para iniciar la conversación.')
                    ->schema([
                        Forms\Components\TextInput::make('subject')
                            ->label('Asunto')
                            ->placeholder('Escriba un título de la conversación')
                            ->required()
                            ->maxLength(75)
                            ->columnSpanFull(),
                            
                        Forms\Components\TextInput::make('description')
                            ->label('Escriba el tema de la conversación')
                            ->placeholder('Escriba su mensaje...')
                            ->columnSpanFull(),
                    ])
                    ->columns(1),

                // Participantes con búsqueda mejorada
                Section::make('Participantes')
                    ->description('Busque y seleccione los participantes de la conversación.')
                    ->schema([
                        Forms\Components\Radio::make('type')
                            ->label('Tipo')
                            ->options([
                                'private' => '👤 Privada (1 persona)',
                                'group' => '👥 Grupal (varias personas)',
                            ])
                            ->default('private')
                            ->inline()
                            ->live()
                            ->columnSpanFull(),

                        Forms\Components\Select::make('participants')
                            ->label(fn ($operation) => $operation === 'edit' ? '👥 Participantes actuales' : '🔍 Buscar participantes')
                            ->multiple(true)
                            ->reactive()
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                if ($get('type') === 'private' && is_array($state) && count($state) > 1) {
                                    $set('participants', [head($state)]);
                                }
                            })
                            ->searchable(fn ($operation) => $operation === 'create')
                            ->preload()
                            ->native(false)
                            ->optionsLimit(50)
                            ->options(function ($operation, $record) {
                                $user = auth()->user();
                                $messagingService = app(MessagingService::class);

                                $allowedRoles = [
                                    'Administrador',
                                    'Obispo Presidente',
                                    'Obispo Viceresidente',
                                    'Tesorero Nacional',
                                    'Contralor Nacional',
                                    'Superintendente Regional',
                                    'Tesorero Regional',
                                    'Contralor Regional',
                                    'Supervisor Distrital',
                                    'Presbítero Sectorial',
                                    'Tesorero Sectorial',
                                    'Contralor Sectorial',
                                ];

                                $getValidParticipants = function ($participants) use ($allowedRoles) {
                                    return $participants->filter(function ($p) use ($allowedRoles) {
                                        return $p->getRoleNames()->intersect($allowedRoles)->isNotEmpty();
                                    });
                                };

                                $available = $getValidParticipants($messagingService->getAvailableParticipants($user));

                                if ($operation === 'edit' && $record) {
                                    $current = $record->participants()->get();
                                    return $current->merge($available)
                                        ->unique('id')
                                        ->mapWithKeys(fn($p) => [$p->id => $p->name . ' ' . $p->lastname]);
                                }

                                return $available->mapWithKeys(fn($p) => [$p->id => $p->name . ' ' . $p->lastname]);
                            })
                            ->getSearchResultsUsing(function (string $search, $operation) {
                                if ($operation === 'edit') return [];

                                $user = auth()->user();
                                $messagingService = app(MessagingService::class);
                                $allowedRoles = [
                                    'Administrador',
                                    'Obispo Presidente',
                                    'Obispo Viceresidente',
                                    'Tesorero Nacional',
                                    'Contralor Nacional',
                                    'Superintendente Regional',
                                    'Tesorero Regional',
                                    'Contralor Regional',
                                    'Supervisor Distrital',
                                    'Presbítero Sectorial',
                                    'Tesorero Sectorial',
                                    'Contralor Sectorial',
                                ];

                                return $messagingService->getAvailableParticipants($user)
                                    ->filter(function ($p) use ($search, $allowedRoles) {
                                        $roleMatch = $p->getRoleNames()->intersect($allowedRoles)->isNotEmpty();
                                        $term = strtolower($search);
                                        $nameMatch = str_contains(strtolower($p->name . ' ' . $p->lastname), $term);
                                        return $roleMatch && $nameMatch;
                                    })
                                    ->take(20)
                                    ->mapWithKeys(fn ($p) => [$p->id => $p->name . ' ' . $p->lastname]);
                            })
                            ->required(fn ($operation) => $operation === 'create')
                            ->rules(function (Forms\Get $get, $operation) {
                                if ($operation === 'edit') return [];

                                return $get('type') === 'private'
                                    ? ['required', 'array', 'size:1']
                                    : ['required', 'array', 'min:2', 'max:50'];
                            })
                            ->columnSpanFull()
                            ->hint(function (Forms\Get $get, $operation, $record) {
                                if ($operation === 'edit' && $record && $record->creator_id !== auth()->id()) {
                                    return 'Solo el creador puede modificar los participantes.';
                                }

                                return $get('type') === 'private'
                                    ? 'Seleccione una sola persona para conversación privada.'
                                    : 'Seleccione al menos dos participantes.';
                            }),



                        // Estado - Solo el creador puede cerrar/cambiar estado
                        Forms\Components\Select::make('status')
                            ->label('📌 Estado')
                            ->options([
                                'active' => '🟢 Activa',
                                'closed' => '🔒 Cerrada',
                                'archived' => '🗃️ Archivada',
                            ])
                            ->default('active')
                            ->required()
                            ->visible(fn ($operation) => $operation === 'edit')
                            ->disabled(fn ($operation, $record) => 
                                $operation === 'edit' && 
                                $record && 
                                $record->creator_id !== auth()->id()
                            )
                            ->helperText(fn ($operation, $record) => 
                                $operation === 'edit' && $record && $record->creator_id !== auth()->id()
                                    ? '⚠️ Solo el creador de la conversación puede cambiar el estado.'
                                    : 'Cambie el estado de la conversación según sea necesario.'
                            )
                            ->columnSpanFull(),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('subject')
                    ->label('Asunto')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Conversation $record): string => 
                        $record->latestMessage?->content 
                            ? \Illuminate\Support\Str::limit(strip_tags($record->latestMessage->content), 50)
                            : 'Sin mensajes'
                    ),
                    
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Creador')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('tipo_dinamico')
                    ->label('Tipo')
                    ->getStateUsing(fn ($record) =>
                        $record->participants->count() === 2 ? 'private' : 'group'
                    )
                    ->colors([
                        'success' => 'private',
                        'primary' => 'group',
                    ])
                    ->icons([
                        'heroicon-o-user' => 'private',
                        'heroicon-o-users' => 'group',
                    ])
                    ->formatStateUsing(fn ($state) => $state === 'private' ? 'Privada' : 'Grupal'),


                    
                Tables\Columns\TextColumn::make('participants_count')
                    ->label('Participantes')
                    ->counts('participants'),
                    
                Tables\Columns\TextColumn::make('messages_count')
                    ->label('Mensajes')
                    ->counts('messages')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('latestMessage.sender.name')
                    ->label('Último mensaje por')
                    ->default('Sin mensajes'),
                    
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Estado')
                    ->colors([
                        'success' => 'active',
                        'warning' => 'closed',
                        'secondary' => 'archived',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'Activa',
                        'closed' => 'Cerrada',
                        'archived' => 'Archivada',
                        default => $state,
                    }),
                    
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Última actividad')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'active' => 'Activa',
                        'closed' => 'Cerrada',
                        'archived' => 'Archivada'
                    ]),
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'group' => 'Grupal',
                        'private' => 'Privada'
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Ver conversación'),
                Tables\Actions\EditAction::make(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->poll('30s');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->visibleTo(auth()->user());
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListConversations::route('/'),
            'create' => Pages\CreateConversation::route('/create'),
            'view' => Pages\ViewConversation::route('/{record}'),
            'edit' => Pages\EditConversation::route('/{record}/edit'),
        ];
    }
}