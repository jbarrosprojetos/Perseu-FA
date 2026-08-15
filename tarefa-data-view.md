A correção anterior de formato de data (AppServiceProvider, defaults de
DatePicker/DateTimePicker) resolveu o formulário de EDIÇÃO, mas a tela
de VISUALIZAR (infolist) do CompanyResource ainda mostra a data em
formato inglês ("jan 1, 2000").

Investigue o componente usado no infolist para exibir esse campo de
data (provavelmente TextEntry::make(...)->date() ou similar, que é
diferente do DatePicker usado no formulário de edição — por isso não
foi coberto pela correção anterior).

Corrija a causa raiz de forma global (não só neste Resource), da mesma
forma que foi feito para DatePicker/DateTimePicker no
AppServiceProvider: adicione o default equivalente para o componente
de data usado em infolists, condicionado ao locale pt_BR.

Ao final, rode ddev artisan optimize:clear.
