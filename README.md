# 📖 Sistema de Publicação de Livros de Receitas (Projeto da Faculdade)

🚀 **Projeto Concluído**  

Este projeto tem como objetivo criar um **Sistema de Publicação de Livros de Receitas**, onde **chefs e funcionários** podem cadastrar, visualizar, editar e excluir receitas organizadas em categorias como **Receitas de Animes**, **Massas**, **Sobremesas**, entre outras.

---

## ✅ Funcionalidades Concluídas

- 🔐 **Autenticação com PHP e MySQL** (login por email/senha)
- 👥 **Controle de Acesso por Cargo** (Administrador, Editor, Cozinheiro, Degustador)
- 📚 **Cadastro de Livros com Múltiplas Receitas**
- 🧾 **Edição e Exclusão de Livros e Receitas**
- 📄 **Geração de PDFs com DomPDF**
- 🧭 **Redirecionamento com base no tipo de usuário**
- 🎨 **Front-end responsivo com HTML, CSS e JavaScript**
- 👁️‍🗨️ **Campo de senha com botão "mostrar/ocultar"**
- 🛡️ **Proteção de rotas com sessões (`$_SESSION`)**
- 🗂️ **Banco de Dados Relacional com MySQL**

---

## 🧪 Testes Automatizados com PHPUnit

Este projeto inclui testes automatizados utilizando o framework **PHPUnit** para validar funcionalidades essenciais, como autenticação e controle de acesso.

Para executar os testes, rode o comando:

```bash
php vendor/bin/phpunit tests/
```

## 🛠️ Tecnologias Utilizadas

- **HTML5** – Estruturação semântica
- **CSS3** – Estilo responsivo e moderno
- **JavaScript** – Funcionalidades dinâmicas (exibir senha, alerts)
- **PHP Puro** – Lógica de back-end e controle de sessão
- **MySQL** – Banco de dados relacional
- **DOMPDF** – Geração de PDFs para livros de receitas
- **Font Awesome** – Ícones modernos

---

## 🧩 Diagrama de Caso de Uso

![Diagrama de Caso de Uso](https://github.com/yarazip/ProjetoLivroDeReceitas/raw/main/CasoDeUso/CasoDeUso.png)

---

## 🗂️ Estrutura de Diretórios

- `/LoginSenha/` – Login e recuperação de senha
- `/Editor/` – Gestão de livros e receitas
- `/ADM/` – Gestão de cargos e funcionários
- `/Cozinheiro/` – Cadastro de receitas
- `/Degustador/` – Visualização e avaliação
- `/BancoDeDados/` – Conexão e scripts SQL
- `/styles/` – Arquivos CSS
- `/assets/` – Imagens e ícones
- `/scripts/` – JavaScript separado (ex: exibir senha)

---

## 🚀 Como Executar o Projeto

1. Clone o repositório:
   ```bash
   git clone https://github.com/yarazip/ProjetoLivroDeReceitas.git
