CREATE TABLE b_pull_stack (
	ID int(18) not null auto_increment,
	CHANNEL_ID varchar(50) not null,
	MESSAGE text not null,
	DATE_CREATE datetime not null,
	PRIMARY KEY (ID),
	KEY IX_PULL_STACK_CID (CHANNEL_ID),
	KEY IX_PULL_STACK_D (DATE_CREATE)
);

CREATE TABLE b_pull_channel (
	ID int(18) not null auto_increment,
	USER_ID int(18) not null,
	CHANNEL_ID varchar(50) not null,
	LAST_ID int(18) null,
	DATE_CREATE datetime not null,
	PRIMARY KEY (ID),
	KEY IX_PULL_CN_UID (USER_ID),
	KEY IX_PULL_CN_CID (CHANNEL_ID),
	KEY IX_PULL_CN_D (DATE_CREATE)
);

CREATE TABLE b_pull_push (
	ID int(18) not null auto_increment,
	USER_ID int(18) not null,
	DEVICE_TYPE varchar(50) null,
	DEVICE_ID varchar(255) null,
	DEVICE_NAME varchar(50) null,
	DEVICE_TOKEN varchar(255) not null,
	DATE_CREATE datetime not null,
	DATE_AUTH datetime null,
	PRIMARY KEY (ID),
	KEY IX_PULL_PSH_UID (USER_ID)
);

CREATE TABLE b_pull_push_queue (
	ID int(18) not null auto_increment,
	USER_ID int(18) not null,
	TAG varchar(255) null,
	MESSAGE varchar(255) not null,
	PARAMS text null,
	BADGE int(11) null,
	DATE_CREATE datetime null,
	PRIMARY KEY (ID),
	KEY IX_PULL_PSHQ_UT (USER_ID, TAG),
	KEY IX_PULL_PSHQ_UID (USER_ID),
	KEY IX_PULL_PSHQ_DC (DATE_CREATE)
);

CREATE TABLE b_pull_watch (
	ID int(18) not null auto_increment,
	USER_ID int(18) not null,
	CHANNEL_ID varchar(50) not null,
	TAG varchar(255) not null,
	DATE_CREATE datetime not null,
	PRIMARY KEY (ID),
	KEY IX_PULL_W_UT (USER_ID, TAG),
	KEY IX_PULL_W_D (DATE_CREATE),
	KEY IX_PULL_W_T (TAG)
);