Preciso de um relatório de auditoria da estrutura deste projeto AureusERP,
SEM alterar nenhum arquivo de código. Analise e me entregue um resumo em
português:

1. Como funciona o controle de acesso: painéis (admin/customer), guards de
   autenticação, e como o Filament Shield gera permissões (Resources,
   Pages, Widgets)
2. Estrutura de cadastro de usuários: onde fica o model User, quais campos
   tem, como se relaciona com Roles
3. Estrutura de Empresas (Companies): como funciona o multi-empresa, quais
   campos a tabela companies tem
4. Onde ficam as configurações de Branding (logo, cores, favicon)

Salve esse relatório em um arquivo chamado AUDITORIA-ESTRUTURA.md na raiz
do projeto.

Depois, crie (ou atualize, se já existir) um arquivo CLAUDE.md na raiz do
projeto, com este conteúdo:

# Contexto do projeto Perseu

Este projeto é baseado no AureusERP, customizado para uma marcenaria
industrial (Perseu).

## Antes de qualquer tarefa de código, consulte:
- AUDITORIA-ESTRUTURA.md — como funciona controle de acesso, usuários,
  empresas e branding

## Convenções e decisões do projeto:
- O cadastro de pessoas será feito como plugin próprio, com tabelas
  separadas para Pessoa Física e Pessoa Jurídica (não o modelo de
  tabela única "partners" do AureusERP original)
- Uma Categoria de Pessoa pode se aplicar a PF, PJ, ou ambos (relação
  muitos-para-muitos)
- Contatos ligam uma Pessoa Física a uma Pessoa Jurídica (representante)
- Usuários de login sempre se vinculam a uma Pessoa Física

## Idioma
Todo o sistema deve ser traduzido/adaptado para português do Brasil,
incluindo campos específicos do Brasil (CPF, CNPJ, RG, Inscrição
Estadual) quando aplicável.
