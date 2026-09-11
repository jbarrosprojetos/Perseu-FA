<?php

namespace Perseu\Comercial\Filament\Clusters\Referencias\Resources;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Perseu\Comercial\Filament\Clusters\Referencias;
use Perseu\Comercial\Filament\Clusters\Referencias\Resources\DocumentoResource\Pages\ListDocumentos;
use Perseu\Comercial\Models\Documento;

/**
 * Cadastro de apoio do Cluster Referências (ver CLAUDE.md, "Documentos
 * — templates de Excel com marcadores de texto") — mesmo padrão
 * técnico de `ReferenciaPrecoResource`/`CondicaoFinanceiraResource`
 * (CRUD em modal, Lixeira própria). Cada registro é um template
 * (.xls/.xlsx/.xlsm) enviado pelo usuário operacional — o mecanismo de
 * marcadores de texto (%Campo%) que o Perseu usa pra preencher esse
 * arquivo com os dados de um Projeto ainda será implementado numa
 * tarefa futura; esta Resource só cobre o CADASTRO do arquivo.
 *
 * SEM trava de exclusão/edição por vínculo (diferente de Referência de
 * Preços/Condição Financeira) — Documento ainda não tem nenhum Model
 * apontando pra ele (`projeto_id`/afins), então não existe vínculo
 * pra travar ainda. Reconsiderar quando a geração de documento por
 * Projeto for implementada, se fizer sentido rastrear qual Documento
 * foi usado em qual geração.
 */
class DocumentoResource extends Resource
{
    protected static ?string $model = Documento::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $cluster = Referencias::class;

    protected static ?string $slug = 'documentos';

    protected static ?int $navigationSort = 3;

    public static function getModelLabel(): string
    {
        return __('comercial::filament/resources/documento.model-label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('comercial::filament/resources/documento.plural-model-label');
    }

    public static function getNavigationLabel(): string
    {
        return __('comercial::filament/resources/documento.navigation.title');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('descricao')
                    ->label(__('comercial::filament/resources/documento.form.descricao'))
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                // Disco `local` (storage/app/, PRIVADO — nunca público),
                // diretório próprio `documentos-templates` — decisão
                // confirmada com o usuário: o arquivo em si nunca vira
                // BLOB no banco (só o caminho, gravado aqui pelo próprio
                // FileUpload na coluna `arquivo`). Sem `->preserveFilenames()`
                // de propósito — o Filament já gera um nome único
                // (evita colisão entre uploads de nomes iguais); a
                // Descrição acima é o que identifica o documento pro
                // usuário, não o nome do arquivo.
                FileUpload::make('arquivo')
                    ->label(__('comercial::filament/resources/documento.form.arquivo'))
                    ->disk('local')
                    ->directory('documentos-templates')
                    ->acceptedFileTypes([
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.ms-excel.sheet.macroEnabled.12',
                    ])
                    ->required()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('descricao')
                    ->label(__('comercial::filament/resources/documento.table.columns.descricao'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('comercial::filament/resources/documento.table.columns.created-at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                TrashedFilter::make()
                    ->label(__('comercial::filament/resources/documento.table.filters.trashed')),
            ])
            ->recordActions([
                EditAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('comercial::filament/resources/documento.table.actions.edit.notification.title'))
                            ->body(__('comercial::filament/resources/documento.table.actions.edit.notification.body')),
                    ),
                DeleteAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('comercial::filament/resources/documento.table.actions.delete.notification.title'))
                            ->body(__('comercial::filament/resources/documento.table.actions.delete.notification.body')),
                    ),
                RestoreAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('comercial::filament/resources/documento.table.actions.restore.notification.title'))
                            ->body(__('comercial::filament/resources/documento.table.actions.restore.notification.body')),
                    ),
                ForceDeleteAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('comercial::filament/resources/documento.table.actions.force-delete.notification.title'))
                            ->body(__('comercial::filament/resources/documento.table.actions.force-delete.notification.body')),
                    ),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
            ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDocumentos::route('/'),
        ];
    }
}
