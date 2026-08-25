<?php

namespace Perseu\Pessoas\Traits;

use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Schema;

/**
 * Adds a visual break between the main form and the Relation Managers on
 * an EditRecord page. Without it, the form's Save/Cancel buttons sit
 * flush against the Section above them and against the Relation Manager
 * table below, even though the Relation Manager is a logically separate
 * area — technically rendered outside the Resource's form() schema
 * entirely (EditRecord::content() stacks getFormContentComponent() and
 * getRelationManagersContentComponent() as two independent pieces).
 *
 * `use` this trait in any {Xxx}Resource's EditRecord page that has at
 * least one Relation Manager (e.g. EditPessoaJuridica). There is no
 * dedicated Divider component in filament/schemas — Html::make('<hr>')
 * is the idiomatic way to drop a plain, self-contained line without
 * fighting Filament's own layout classes.
 */
trait HasRelationManagerDividers
{
    /**
     * A few DOM layers below the Form component's own root element, the
     * embedded form schema (EmbeddedSchema::make('form')) renders as a
     * SINGLE wrapping ".fi-grid" — one direct flex child of
     * ".fi-sc-form" (the other being the ->footer() block). Filament's
     * Form (".fi-sc-form") is `flex flex-col gap-6`, and that gap is
     * what separates those two flex children, so overriding it directly
     * (inline style, same technique as flexRow()'s gap) is what reliably
     * controls the *visible* space between "the form" and the
     * Save/Cancel buttons, regardless of which field happens to be last
     * inside the embedded form.
     *
     * The qalainau/bonsai-theme package, installed in this project until
     * it was removed for good (see CLAUDE.md), shipped `.fi-sc-form,
     * .fi-sc-form.fi-dense { gap: 0 !important; }` as part of its
     * zero-gap, high-density design. An author stylesheet's !important
     * always wins over a non-important inline style regardless of
     * specificity, so this needed its own `!important` to actually win —
     * without it, every gap value tried here (4rem, then 6rem) was
     * silently zeroed out with no visible effect. Confirmed at the time
     * by reading vendor/qalainau/bonsai-theme/resources/css/bonsai.css;
     * Bonsai shipped no config/exclusion mechanism to opt specific
     * elements out (its plugin class only registered a static CSS file,
     * nothing else). Kept even after Bonsai's removal: harmless without
     * a competing !important, and cheap insurance against another
     * package doing the same thing later.
     *
     * Once the !important actually started winning, 6rem (picked while
     * the gap had no visible effect, to compensate for the "Endereços"
     * Section's removed visual weight — see git history) turned out to
     * be too much on screen. 2rem is the value that reads right now
     * that the gap is real.
     */
    protected static function formFooterGap(): string
    {
        return '2rem';
    }

    protected static function relationManagerSectionBreakHtml(): string
    {
        return '<div class="my-12 space-y-4">'
            .'<hr class="border-t-2 border-gray-200 dark:border-white/10" />'
            .'<hr class="border-t-2 border-gray-200 dark:border-white/10" />'
            .'</div>';
    }

    /**
     * Same bg/rounding tokens Filament's own Section uses for its
     * ".fi-secondary" variant (bg-gray-50 / dark:bg-white/5) — reused
     * here instead of picking an arbitrary color, so the Relation
     * Manager area reads as a distinct "card" using a shade the rest of
     * the panel already establishes as meaning "secondary content".
     */
    protected static function relationManagerContainerClasses(): string
    {
        return 'rounded-xl bg-gray-50 p-6 dark:bg-white/5';
    }

    /**
     * Space (no line) between the end of the form (last Section/field)
     * and the Save/Cancel buttons, which Filament renders as the form's
     * own ->footer() — i.e. still inside getFormContentComponent(), not
     * a sibling of it, so this is the only place that can reach "right
     * before the buttons" without editing the Resource's form().
     */
    public function getFormContentComponent(): Component
    {
        if (! $this->hasFormWrapper()) {
            return parent::getFormContentComponent();
        }

        return Form::make([
            EmbeddedSchema::make('form'),
        ])
            ->id('form')
            ->livewireSubmitHandler($this->getSubmitFormLivewireMethodName())
            ->extraAttributes([
                'style' => 'gap: '.static::formFooterGap().' !important;',
            ])
            ->footer([
                $this->getFormActionsContentComponent(),
            ]);
    }

    /**
     * Heavier double-line break between the Save/Cancel buttons and the
     * Relation Manager area, plus a subtle background "card" around the
     * Relation Manager itself, to make the change of section obvious —
     * a thin line alone still read as one continuous block.
     */
    public function content(Schema $schema): Schema
    {
        if ($this->hasCombinedRelationManagerTabsWithContent()) {
            return parent::content($schema);
        }

        return $schema
            ->components([
                $this->getFormContentComponent(),
                Html::make(static::relationManagerSectionBreakHtml()),
                Group::make([
                    $this->getRelationManagersContentComponent(),
                ])->extraAttributes([
                    'class' => static::relationManagerContainerClasses(),
                ]),
            ]);
    }
}
