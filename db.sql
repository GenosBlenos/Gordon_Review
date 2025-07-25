CREATE DATABASE biblioteca;
USE biblioteca;

CREATE TABLE pm_emprest_uni
(   
    CodEmprest BIGINT PRIMARY KEY AUTO_INCREMENT,
    CodBarras BIGINT,
    Tipo VARCHAR(20) NOT NULL,
    Descricao VARCHAR(100),
    Usuario INT NOT NULL,
    DataEmprestimo DATE NOT NULL,
    DataPrevista DATE NOT NULL,
    DataDevolucao DATE,
    Multa INT DEFAULT 0,
    DiasAtraso INT DEFAULT 0,
    Pago BOOLEAN DEFAULT FALSE,
    DevolOK BOOLEAN DEFAULT FALSE
)

CREATE TABLE pm_acer1
(
    CODIGO INT PRIMARY KEY,
    OBSERVA VARCHAR(100),
    OBSERVA1 VARCHAR(100),
    OBSERVA2 VARCHAR(100),
    OBSERVA3 VARCHAR(100),
    OBSERVA4 VARCHAR(100),
    ASSUNTO VARCHAR(100),
    ASSUNTO1 VARCHAR(100),
    ASSUNTO2 VARCHAR(100),
    ASSUNTO3 VARCHAR(100),
    ASSUNTO4 VARCHAR(100),
    AUTORES VARCHAR(100),
    AUTORES1 VARCHAR(100),
    AUTORES2 VARCHAR(100),
    AUTORES3 VARCHAR(100),
    AUTORES4 VARCHAR(100),
    TRADUTORES VARCHAR(100),
    TRADUTOR1 VARCHAR(100),
    TRADUTOR2 VARCHAR(100),
    TRADUTOR3 VARCHAR(100),
    TRADUTOR4 VARCHAR(100)
)

CREATE TABLE pm_acerv_uni
(
    CODIGO INT PRIMARY KEY AUTO_INCREMENT,
    ISBN VARCHAR(20),
    PAIS VARCHAR(20) NOT NULL,
    TIPO VARCHAR(50) NOT NULL,
    CLASSIFICA VARCHAR(50) NOT NULL,
    TITULO VARCHAR(255) NOT NULL,
    AUTOR VARCHAR(100) NOT NULL,
    INICIALTIT VARCHAR(2) NOT NULL,
    EDICAO VARCHAR(10) NOT NULL,
    TEMA VARCHAR(20) NOT NULL,
    LOCALPUB VARCHAR(35) NOT NULL,
    ESTADO VARCHAR(35) NOT NULL,
    ANO INT NOT NULL,
    AQUISICAO VARCHAR(20) NOT NULL,
    ENTRADA DATE NOT NULL,
    VOLUME INT NOT NULL,
    ORIGEM VARCHAR(20) NOT NULL,
    VALOR FLOAT,
    PAGINAS INT NOT NULL,
    SERIE VARCHAR(20),
    COLECAO VARCHAR(20),
    ASPECTO VARCHAR(20),
    IDIOMA VARCHAR(20),
    EMPREST BOOLEAN DEFAULT FALSE,
    CODI_BARRA BIGINT UNIQUE,
    EDITORA VARCHAR(100),
    CUTTER VARCHAR(20),
    ASSUNTO VARCHAR(100),
    DATAPUB DATE,
    CodEditora VARCHAR(20),
    Etiqueta BOOLEAN DEFAULT FALSE,
    Sala INT,
    Estante INT,
    Inventario BOOLEAN DEFAULT FALSE
);

CREATE TABLE pm_edit
(
    CODIGO INT PRIMARY KEY,
    NOME VARCHAR(50),
    ENDERECO VARCHAR(225),
    COMPLEMENTO VARCHAR(40),
    SALA_BOX VARCHAR(50),
    CIDADE VARCHAR(35),
    BAIRRO VARCHAR(35),
    CEP VARCHAR(10),
    ESTADO VARCHAR(2),
    CGC_MF VARCHAR(20),
    INSC_EST VARCHAR(20),
    TELEFONE VARCHAR(20),
    TEL_FAX VARCHAR(20),
    CONTATO VARCHAR(50),
    CodEditora VARCHAR(20),
)

CREATE TABLE pm_est
(
    CODIGO INT PRIMARY KEY,
    SIGLA VARCHAR(10) NOT NULL,
    NOME VARCHAR(100) NOT NULL,
)

CREATE TABLE pm_pais
(
    IDIOMA VARCHAR(20),
    PAIS VARCHAR(20),
    CODIGO INT PRIMARY KEY,
)

CREATE TABLE reserva
(
    CODIGO INT PRIMARY KEY,
    TITULO VARCHAR(50) NOT NULL,
    USUARIO INT NOT NULL,
    NOME VARCHAR(100) NOT NULL,
    DATARES DATE NOT NULL,
    HORA TIME NOT NULL,
)

CREATE TABLE pm_usua
(
    CODIGO INT AUTO_INCREMENT PRIMARY KEY,
    NOME VARCHAR(100) NOT NULL,
    ENDERECO VARCHAR(255) NOT NULL,  
    CIDADE VARCHAR(20) NOT NULL,
    BAIRRO VARCHAR(100),
    CEP VARCHAR(10) NOT NULL,
    ESTADO VARCHAR(2) NOT NULL,
    RG VARCHAR(20) NOT NULL,
    CIC VARCHAR(20) NOT NULL,
    TELEFONE VARCHAR(20) NOT NULL,
    CODBARRAS INT UNIQUE,
    QTD INT DEFAULT 1,
    DATACAD DATE,
    EMAIL VARCHAR(100),
    SENHA VARCHAR(255) NOT NULL
);

    CREATE TABLE pm_admin
    (
        MATRICULA INT PRIMARY KEY,
        NOME VARCHAR(100) NOT NULL,
        SENHA VARCHAR(20) NOT NULL,
        EMAIL VARCHAR(100) NOT NULL,
        TELEFONE VARCHAR(20) NOT NULL,
        CPF VARCHAR(11) NOT NULL,
    )

    CREATE TABLE pagamentos
    (
        id INT
        AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    valor DECIMAL
        (10,2) NOT NULL,
    data_pagamento DATETIME NOT NULL,
    FOREIGN KEY
        (usuario_id) REFERENCES pm_usua
        (CODIGO)
);


-- EXEMPLO de inserção de dados na tabela pm_acerv
--
-- INSERT INTO pm_acerv (ISBN, PAIS, TIPO, CODIGO, CLASSIFICA, TITULO, AUTOR, INICIALTIT, EDICAO,
-- TEMA, LOCALPUB, ESTADO, ANO, STATUSLIV, ENTRADA, VOLUME, ORIGEM, VALOR, PAGINAS, SERIE, COLECAO,
-- ASPECTO, IDIOMA, EMPRESTIMO, QUANTIDADE, EDITORA, CUTTER, CodEditora, Etiqueta, Sala, Estante, Inventario)
-- VALUES (
--     '9788566636239',     -->  ISBN 
--     'BRASIL',            -->  PAIS
--     'LIVRO',             -->  TIPO
--     '654654654',         -->  CODIGO
--     'Suspense',          -->  CLASSIFICA
--     'DRACULA',           -->  TITULO
--     'Bram Stoker',       -->  AUTOR
--     'D',                 -->  INICIALTIT
--     '1',                 -->  EDICAO
--     'Romance',           -->  TEMA
--     'Rio de Janeiro',    -->  LOCALPUB
--     'RJ',                -->  ESTADO
--     '2018',              -->  ANO
--     'COMPRA',            -->  STATUSLIV
--     '2018-08-08',        -->  ENTRADA
--     '1',                 -->  VOLUME
--     'BIBLIOTECA',        -->  ORIGEM
--     '0.0',               -->  VALOR
--     '580',               -->  PAGINAS
--     '1',                 -->  SERIE
--     'Medo Clássico',     -->  COLECAO
--     'B',                 -->  ASPECTO (Bom estado)
--     'PORTUGUES',         -->  IDIOMA   
--     'N',                 -->  EMPRESTIMO (S=sim N=nao)
--     '978-8566636239',    -->  CODBARRAS
--     'DARKSIDE BOOKS',    -->  EDITORA
--     'B.580',             -->  CUTTER
--     '784',               -->  CodEditora
--     'FALSE',             -->  Etiqueta
--     '2',                 -->  Sala
--     '1',                 -->  Estante
--     'FALSE'              -->  Inventario
-- );


-- -- Inserindo um administrador padrão
-- INSERT INTO pm_admin (
--     MATRICULA, 
--     NOME, 
--     SENHA, 
--     EMAIL, 
--     TELEFONE, 
--     CPF
-- )
--
-- VALUES (
--     1001,                        --> Matrícula única
--     'Administrador Principal',   --> Nome
--     'SenhaSegura123',            --> Senha
--     'admin@biblioteca.com',      --> Email
--     '(11) 99999-9999',           --> Telefone
--     '12345678900'                --> CPF
-- );
