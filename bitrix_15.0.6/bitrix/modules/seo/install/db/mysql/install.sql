create table if not exists b_seo_keywords
(
	ID int(11) not null auto_increment,
	SITE_ID CHAR(2) not null,
	URL varchar(255),
	KEYWORDS text null,
	PRIMARY KEY (ID),
	INDEX ix_b_seo_keywords_url (URL, SITE_ID)
);

create table if not exists b_seo_search_engine
(
	ID int(11) NOT NULL auto_increment,
	CODE varchar(50) NOT NULL,
	ACTIVE char(1) NULL default 'Y',
	SORT int(5) NULL default 100,
	NAME varchar(255) NOT NULL,
	CLIENT_ID varchar(255) NULL,
	CLIENT_SECRET varchar(255) NULL,
	REDIRECT_URI varchar(255) NULL,
	SETTINGS text NULL,
	PRIMARY KEY (ID),
	UNIQUE INDEX ux_b_seo_search_engine_code (CODE)
);

INSERT INTO b_seo_search_engine (CODE, ACTIVE, SORT, NAME, CLIENT_ID, CLIENT_SECRET, REDIRECT_URI) VALUES ('google', 'Y', 200, 'Google', '950140266760.apps.googleusercontent.com', 'IBktWJ_dS3rMKh43PSHO-zo5', 'urn:ietf:wg:oauth:2.0:oob');
INSERT INTO b_seo_search_engine (CODE, ACTIVE, SORT, NAME, CLIENT_ID, CLIENT_SECRET, REDIRECT_URI) VALUES ('yandex', 'Y', 300, 'Yandex', 'f848c7bfc1d34a94ba6d05439f81bbd7', 'da0e73b2d9cc4e809f3170e49cb9df01', 'https://oauth.yandex.ru/verification_code');
INSERT INTO b_seo_search_engine (CODE, ACTIVE, SORT, NAME, CLIENT_ID, CLIENT_SECRET, REDIRECT_URI) VALUES ('yandex_direct', 'Y', 400, 'Yandex.Direct', 'e46a29a748d84036baee1e2ae2a84fc6', '7122987f5a99479bb756d79ed7986e6c', 'https://oauth.yandex.ru/verification_code');

create table if not exists b_seo_sitemap
(
	ID int(11) NOT NULL auto_increment,
	TIMESTAMP_X timestamp,
	SITE_ID char(2) NOT NULL,
	ACTIVE char(1) NULL default 'Y',
	NAME varchar(255) NULL default '',
	DATE_RUN datetime NULL default NULL,
	SETTINGS longtext NULL,
	PRIMARY KEY (ID)
);

create table if not exists b_seo_sitemap_runtime
(
	ID int(11) NOT NULL auto_increment,
	PID int (11) NOT NULL,
	PROCESSED char(1) NOT NULL DEFAULT 'N',
	ITEM_PATH varchar(700) NULL,
	ITEM_ID int(11) NULL,
	ITEM_TYPE char(1) NOT NULL DEFAULT 'D',
	ACTIVE char(1) NULL DEFAULT 'Y',
	ACTIVE_ELEMENT char(1) NULL DEFAULT 'Y',
	PRIMARY KEY (ID),
	INDEX ix_seo_sitemap_runtime1 (PID, PROCESSED, ITEM_TYPE, ITEM_ID)
);

CREATE TABLE if not exists b_seo_sitemap_iblock
(
	ID int(11) NOT NULL auto_increment,
	IBLOCK_ID int(11) NOT NULL,
	SITEMAP_ID int(11) NOT NULL,
	PRIMARY KEY (ID),
	INDEX ix_b_seo_sitemap_iblock_1 (IBLOCK_ID),
	INDEX ix_b_seo_sitemap_iblock_2 (SITEMAP_ID)
);

CREATE TABLE if not exists b_seo_sitemap_entity
(
	ID int(11) NOT NULL auto_increment,
	ENTITY_TYPE varchar(255) NOT NULL,
	ENTITY_ID int(11) NOT NULL,
	SITEMAP_ID int(11) NOT NULL,
	PRIMARY KEY (ID),
	INDEX ix_b_seo_sitemap_entity_1 (ENTITY_TYPE, ENTITY_ID),
	INDEX ix_b_seo_sitemap_entity_2 (SITEMAP_ID)
);

CREATE TABLE if not exists b_seo_adv_campaign
(
	ID int(11) NOT NULL auto_increment,
	ENGINE_ID int(11) NOT NULL,
	ACTIVE char(1) NOT NULL DEFAULT 'Y',
	OWNER_ID varchar(255) NOT NULL,
	OWNER_NAME varchar(255) NOT NULL,
	XML_ID varchar(255) NOT NULL,
	NAME varchar(255) NOT NULL,
	LAST_UPDATE timestamp NULL,
	SETTINGS text NULL,
	PRIMARY KEY (ID),
	UNIQUE INDEX ux_b_seo_adv_campaign(ENGINE_ID, XML_ID)
);

CREATE TABLE if not exists b_seo_adv_group
(
	ID int(11) NOT NULL auto_increment,
	ENGINE_ID int(11) NOT NULL,
	OWNER_ID varchar(255) NOT NULL,
	OWNER_NAME varchar(255) NOT NULL,
	ACTIVE char(1) NULL DEFAULT 'Y',
	XML_ID varchar(255) NOT NULL,
	LAST_UPDATE timestamp NULL,
	NAME varchar(255) NOT NULL,
	SETTINGS text NULL,
	CAMPAIGN_ID int(11) NOT NULL,
	PRIMARY KEY (ID),
	UNIQUE INDEX ux_b_seo_adv_group(ENGINE_ID, XML_ID),
	INDEX ix_b_seo_adv_group1(CAMPAIGN_ID)
);

CREATE TABLE if not exists b_seo_adv_banner
(
	ID int(11) NOT NULL auto_increment,
	ENGINE_ID int(11) NOT NULL,
	OWNER_ID varchar(255) NOT NULL,
	OWNER_NAME varchar(255) NOT NULL,
	ACTIVE char(1) NULL DEFAULT 'Y',
	XML_ID varchar(255) NOT NULL,
	LAST_UPDATE timestamp NULL,
	NAME varchar(255) NOT NULL,
	SETTINGS text NULL,
	CAMPAIGN_ID int(11) NOT NULL,
	GROUP_ID int(11) NULL,
	PRIMARY KEY (ID),
	UNIQUE INDEX ux_b_seo_adv_banner(ENGINE_ID, XML_ID),
	INDEX ix_b_seo_adv_banner1(CAMPAIGN_ID)
);


CREATE TABLE if not exists b_seo_adv_region
(
	ID int(11) NOT NULL auto_increment,
	ENGINE_ID int(11) NOT NULL,
	OWNER_ID varchar(255) NOT NULL,
	OWNER_NAME varchar(255) NOT NULL,
	ACTIVE char(1) NULL DEFAULT 'Y',
	XML_ID varchar(255) NOT NULL,
	LAST_UPDATE timestamp NULL,
	NAME varchar(255) NOT NULL,
	SETTINGS text NULL,
	PARENT_ID int(11) NOT NULL,
	PRIMARY KEY (ID),
	UNIQUE INDEX ux_b_seo_adv_region(ENGINE_ID, XML_ID),
	INDEX ix_b_seo_adv_region1(PARENT_ID)
);

CREATE TABLE if not exists b_seo_adv_link
(
	LINK_TYPE char(1) NOT NULL,
	LINK_ID int(18) NOT NULL,
	BANNER_ID int(11) NOT NULL,
	PRIMARY KEY (LINK_TYPE,LINK_ID,BANNER_ID)
);

CREATE TABLE if not exists b_seo_adv_order
(
	ID int(11) NOT NULL auto_increment,
	ENGINE_ID int(11) NOT NULL,
	TIMESTAMP_X timestamp NOT NULL,
	CAMPAIGN_ID int(11) NOT NULL,
	BANNER_ID int(11) NOT NULL,
	ORDER_ID int(11) NOT NULL,
	SUM FLOAT NULL DEFAULT 0,
	PROCESSED char(1) NULL DEFAULT 'N',
	PRIMARY KEY (ID),
	UNIQUE INDEX ux_b_seo_adv_order (ENGINE_ID, CAMPAIGN_ID, BANNER_ID, ORDER_ID),
	INDEX ix_b_seo_adv_order1 (ORDER_ID, PROCESSED)
);

CREATE TABLE if not exists b_seo_adv_log
(
	ID int(11) NOT NULL AUTO_INCREMENT,
	ENGINE_ID int(11) NOT NULL,
	TIMESTAMP_X timestamp NOT NULL,
	REQUEST_URI varchar(100) NOT NULL,
	REQUEST_DATA text,
	RESPONSE_TIME float NOT NULL,
	RESPONSE_STATUS int(5),
	RESPONSE_DATA text,
	PRIMARY KEY (ID),
	INDEX ix_b_seo_adv_log1 (ENGINE_ID),
	INDEX ix_b_seo_adv_log2 (TIMESTAMP_X)
);

CREATE TABLE if not exists b_seo_yandex_direct_stat
(
	ID int(18) NOT NULL AUTO_INCREMENT,
	CAMPAIGN_ID int(11) NOT NULL,
	BANNER_ID int(11) NOT NULL,
	DATE_DAY DATE NOT NULL,
	CURRENCY CHAR(3) NULL,
	SUM float NULL default 0,
	SUM_SEARCH float NULL default 0,
	SUM_CONTEXT float NULL default 0,
	CLICKS int(7) NULL default 0,
	CLICKS_SEARCH int(7) NULL default 0,
	CLICKS_CONTEXT int(7) NULL default 0,
	SHOWS int(7) NULL default 0,
	SHOWS_SEARCH int(7) NULL default 0,
	SHOWS_CONTEXT int(7) NULL default 0,
	PRIMARY KEY (ID),
	UNIQUE INDEX ux_seo_yandex_direct_stat (BANNER_ID,DATE_DAY),
	INDEX ix_seo_yandex_direct_stat1 (CAMPAIGN_ID)
);