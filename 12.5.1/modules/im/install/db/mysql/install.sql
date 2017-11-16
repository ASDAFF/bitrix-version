CREATE TABLE b_im_chat(
	ID int(18) not null auto_increment,
	TITLE varchar(255) null,
	AUTHOR_ID int(18) not null,
	PRIMARY KEY (ID),
	KEY IX_IM_CHAT_1 (AUTHOR_ID)
);

CREATE TABLE b_im_message(
	ID int(18) not null auto_increment,
	CHAT_ID int(18) not null,
	AUTHOR_ID int(18) not null,
	MESSAGE text null,
	MESSAGE_OUT text null,
	DATE_CREATE datetime not null,
	EMAIL_TEMPLATE varchar(255) null,
	NOTIFY_TYPE smallint(2) DEFAULT 0,
	NOTIFY_MODULE varchar(255) null,
	NOTIFY_EVENT varchar(255) null,
	NOTIFY_TAG varchar(255) null,
	NOTIFY_SUB_TAG varchar(255) null,
	NOTIFY_TITLE varchar(255) null,
	NOTIFY_BUTTONS text null,
	NOTIFY_READ char(1) DEFAULT 'N',
	IMPORT_ID int(18) null,
	PRIMARY KEY (ID),
	KEY IX_IM_MESS_1 (CHAT_ID),
	KEY IX_IM_MESS_2 (NOTIFY_TAG, AUTHOR_ID),
	KEY IX_IM_MESS_3 (NOTIFY_SUB_TAG, AUTHOR_ID)
);

CREATE TABLE b_im_relation(
	ID int(18) not null auto_increment,
	CHAT_ID int(18) not null,
	MESSAGE_TYPE char(2) default 'P',
	USER_ID int(18) not null,
	START_ID int(18) DEFAULT 0,
	LAST_ID int(18) DEFAULT 0,
	LAST_SEND_ID int(18) DEFAULT 0,
	STATUS smallint(1) DEFAULT 0,
	PRIMARY KEY (ID),
	KEY IX_IM_REL_1 (CHAT_ID),
	KEY IX_IM_REL_2 (USER_ID, MESSAGE_TYPE, STATUS),
	KEY IX_IM_REL_3 (USER_ID, MESSAGE_TYPE, CHAT_ID),
	KEY IX_IM_REL_4 (USER_ID, STATUS),
	KEY IX_IM_REL_5 (MESSAGE_TYPE, STATUS)
);

CREATE TABLE b_im_recent(
	USER_ID int(18) not null,
	ITEM_TYPE char(2) default 'P' not null,
	ITEM_ID int(18) not null,
	ITEM_MID int(18) not null,
	PRIMARY KEY (USER_ID, ITEM_TYPE, ITEM_ID),
	KEY IX_IM_REC_1 (ITEM_TYPE, ITEM_ID)
);
