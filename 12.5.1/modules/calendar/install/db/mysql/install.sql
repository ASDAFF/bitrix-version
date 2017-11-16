create table b_calendar_type
(
	XML_ID varchar(255) not null,
	NAME  varchar(255) null,
	DESCRIPTION text null,
	EXTERNAL_ID varchar(100) null,
	ACTIVE char(1) not null default 'Y',
	primary key (XML_ID)
);

create table b_calendar_section
(
	ID int not null auto_increment,
	NAME varchar(255) null,
	XML_ID varchar(100) null,
	EXTERNAL_ID varchar(100) null,
	ACTIVE char(1) not null default 'Y',
	DESCRIPTION text null,
	COLOR varchar(10) null,
	TEXT_COLOR varchar(10) null,
	EXPORT varchar(255) null,
	SORT int not null default 100,
	CAL_TYPE varchar(100) null,
	OWNER_ID int null,
	CREATED_BY int not null,
	PARENT_ID int null,
	DATE_CREATE  datetime null,
	TIMESTAMP_X  datetime null,
	DAV_EXCH_CAL varchar(255) null,
	DAV_EXCH_MOD varchar(255) null,
	CAL_DAV_CON varchar(255) null,
	CAL_DAV_CAL varchar(255) null,
	CAL_DAV_MOD varchar(255) null,
	IS_EXCHANGE char(1) null,
	primary key (ID),
	INDEX ix_cal_sect_owner (CAL_TYPE, OWNER_ID)
);

create table b_calendar_event
(
	ID int not null auto_increment,
	ACTIVE char(1) not null default 'Y',
	DELETED char(1) not null default 'N',
	CAL_TYPE varchar(100) null,
	OWNER_ID int not null,
	CREATED_BY int not null,
	DATE_CREATE  datetime null,
	TIMESTAMP_X  datetime null,
	NAME varchar(255) null,
	DESCRIPTION text null,
	DT_FROM datetime null,
	DT_TO datetime null,
	DT_SKIP_TIME char(1) null,
	DT_LENGTH bigint null,
	PRIVATE_EVENT char(10) null,
	ACCESSIBILITY char(10) null,
	IMPORTANCE char(10) null,
	IS_MEETING char(1) null,
	MEETING_HOST int null,
	MEETING text null,
	LOCATION varchar(255) null,
	REMIND varchar(255) null,
	EXTERNAL_ID varchar(255) null,
	COLOR varchar(10) null,
	TEXT_COLOR varchar(10) null,
	RRULE varchar(255) null,
	EXRULE varchar(255) null,
	RDATE text null,
	EXDATE text null,
	DAV_XML_ID varchar(255) null,
	DAV_EXCH_LABEL varchar(255) null,
	CAL_DAV_LABEL varchar(255) null,
	VERSION varchar(255) null,
	primary key (ID),
	INDEX ix_cal_event_date (DT_TO, DT_FROM)
);

create table b_calendar_event_sect
(
	EVENT_ID int not null,
	SECT_ID int not null,
	REL  char(10) null,
	primary key (EVENT_ID, SECT_ID)
);

create table b_calendar_attendees
(
	EVENT_ID int not null,
	USER_KEY varchar(255) not null,
	USER_ID int null,
	USER_EMAIL varchar(255) null,
	USER_NAME varchar(255) null,

	STATUS char(10) not null default 'Q',
	DESCRIPTION varchar(255) null,
	ACCESSIBILITY char(10) null,
	REMIND varchar(255) null,
	SECT_ID int null,

	COLOR varchar(10) null,
	TEXT_COLOR varchar(10) null,

	primary key (EVENT_ID, USER_KEY)
);

create table b_calendar_access
(
	ACCESS_CODE varchar(100) not null,
	TASK_ID int not null,
	SECT_ID varchar(100) not null,
	primary key (ACCESS_CODE, TASK_ID, SECT_ID)
);