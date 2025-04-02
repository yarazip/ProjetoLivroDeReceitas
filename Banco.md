👥 Tabelas de Usuários
Usuario (Tabela principal)

Armazena informações básicas de todos os usuários.

Campos:

email: Chave primária (identificador único).

tipo: Define o perfil do usuário (cozinheiro, administrador, etc.).

Obs.: O campo tipo usa ENUM para garantir apenas valores pré-definidos.

Tabelas Especializadas (Cozinheiro, Administrador, Degustador, Editor):

Cada uma estende a tabela Usuario com informações específicas.

Todas usam email como chave primária e estrangeira (relacionamento 1:1 com Usuario).

Exemplo: Cozinheiro tem o campo referencias para informações profissionais.

🍳 Tabelas de Receitas
Receita

Armazena informações gerais das receitas.

Campos importantes:

cozinheiro_email: Relaciona a receita ao cozinheiro que a criou.

dificuldade: Classificação pré-definida (Fácil, Médio, Difícil).

Ingrediente

Catálogo de todos os ingredientes possíveis.

unidade_medida: Indica a unidade padrão do ingrediente (ex: "gramas", "xícaras").

Medida

Define unidades de medida (ex: "colher de sopa", "litro").

ReceitaIngrediente (Tabela de relacionamento)

Liga receitas aos ingredientes, com informações adicionais:

quantidade: Quanto do ingrediente é usado (ex: 200).

medida_id: A unidade de medida específica para essa receita (ex: "gramas").

⭐ Sistema de Avaliações
Avaliacao

Permite que degustadores avaliem receitas.

Campos:

nota: Valor entre 0 e 10 (usando CHECK para validação).

comentario: Feedback textual.

Relaciona receita_id e degustador_email.

📚 Livros de Receitas
Livro

Armazena livros ou coleções de receitas.

LivroReceita (Tabela de relacionamento)

Liga livros às receitas (relacionamento muitos-para-muitos).

🔒 Recuperação de Senha
RecuperacaoSenha

Gerencia tokens para redefinição de senha.

Campos:

token: Código único temporário.

expiracao: Data/hora de validade.

status: Controla se o token está ativo ou expirado.

🔗 Relacionamentos Chave
Herança de Usuários:
As tabelas especializadas (Cozinheiro, Administrador, etc.) herdam de Usuario através do email (chave estrangeira).

Receitas:

Pertencem a um Cozinheiro.

Podem ter múltiplos Ingredientes (via ReceitaIngrediente).

Podem estar em múltiplos Livros.

Integridade Referencial:

Uso de ON DELETE CASCADE: Se um usuário for deletado, todos os dados relacionados (receitas, avaliações) também são removidos automaticamente.

Criação de Índices (Otimização de Buscas)
🔍 CREATE INDEX idx_receita_nome ON Receita(nome);
O que faz:
Cria um índice chamado idx_receita_nome no campo nome da tabela Receita.

Por que é importante:

Torna buscas por nome (ex: WHERE nome LIKE '%bolo%') até 100x mais rápidas

Reduz o tempo de carregamento em listagens de receitas.

💡 Pontos Fortes do Design
Normalização:

Dados estão bem distribuídos sem repetições.

Uso apropriado de tabelas de relacionamento.

Controle de Acesso:

O campo tipo em Usuario permite gerenciar perfis de forma eficiente.

Extensibilidade:

Fácil adicionar novos tipos de usuários ou campos.