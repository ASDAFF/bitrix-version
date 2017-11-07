create table b_catalog_currency
(
	CURRENCY char(3) not null,
	AMOUNT_CNT int not null default 1,
	AMOUNT decimal(18, 4) null,
	SORT int not null default 100,
	DATE_UPDATE datetime not null,
	primary key (CURRENCY)
);

create table b_catalog_currency_lang
(
	CURRENCY char(3) not null,
	LID char(2) not null,
	FORMAT_STRING varchar(50) not null,
	FULL_NAME varchar(50) null,
	DEC_POINT varchar(5) null default '.',
	THOUSANDS_SEP varchar(5) null default ' ',
	DECIMALS tinyint not null default 2,
	THOUSANDS_VARIANT CHAR(1) null,
	primary key (CURRENCY, LID)
);

create table b_catalog_currency_rate
(
	ID int not null auto_increment,
	CURRENCY char(3) not null,
	DATE_RATE date not null,
	RATE_CNT int not null default 1,
	RATE decimal(18, 4) not null default 0.00,
	primary key (ID),
	unique IX_CURRENCY_RATE(CURRENCY, DATE_RATE)
);

