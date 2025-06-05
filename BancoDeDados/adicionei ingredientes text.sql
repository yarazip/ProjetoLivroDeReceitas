USE teste_trabalho_1; -- Garante que você está no banco de dados certo

ALTER TABLE receitas
ADD COLUMN ingredientes_lista_texto TEXT NULL AFTER dificuldade;
-- Ou AFTER descricao, se preferir que ela fique no final