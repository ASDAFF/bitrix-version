CREATE TABLE b_bp_workflow_template (
	ID int NOT NULL auto_increment,
	MODULE_ID varchar(32) NULL,
	ENTITY varchar(64) NOT NULL,
	DOCUMENT_TYPE varchar(128) NOT NULL,
	AUTO_EXECUTE int NOT NULL DEFAULT 0,
	NAME varchar(255) NULL,
	DESCRIPTION text NULL,
	TEMPLATE blob NULL,
	PARAMETERS blob NULL,
	VARIABLES blob NULL,
	MODIFIED datetime NOT NULL,
	USER_ID int NULL,
	SYSTEM_CODE varchar(50),
	ACTIVE char(1) NOT NULL default 'Y',
	primary key (ID),
	index ix_bp_wf_template_mo(MODULE_ID, ENTITY, DOCUMENT_TYPE)
);

CREATE TABLE b_bp_workflow_state (
	ID varchar(32) NOT NULL,
	MODULE_ID varchar(32) NULL,
	ENTITY varchar(64) NOT NULL,
	DOCUMENT_ID varchar(128) NOT NULL,
	DOCUMENT_ID_INT int NOT NULL,
	WORKFLOW_TEMPLATE_ID int NOT NULL,
	STATE varchar(128) NULL,
	STATE_TITLE varchar(255) NULL,
	STATE_PARAMETERS text NULL,
	MODIFIED datetime NOT NULL,
	STARTED datetime NULL,
	STARTED_BY int NULL,
	primary key (ID),
	index ix_bp_ws_document_id(DOCUMENT_ID, ENTITY, MODULE_ID),
	index ix_bp_ws_document_id1(DOCUMENT_ID_INT, ENTITY, MODULE_ID, STATE)
);

CREATE TABLE b_bp_workflow_permissions (
	ID int NOT NULL auto_increment,
	WORKFLOW_ID varchar(32) NOT NULL,
	OBJECT_ID varchar(64) NOT NULL,
	PERMISSION varchar(64) NOT NULL,
	primary key (ID),
	index ix_bp_wf_permissions_wt(WORKFLOW_ID)
);

CREATE TABLE b_bp_workflow_instance (
	ID varchar(32) NOT NULL,
	WORKFLOW blob NULL,
	STATUS int NULL,
	MODIFIED datetime NOT NULL,
	OWNER_ID varchar(32) NULL,
	OWNED_UNTIL datetime NULL,
	primary key (ID)
);

CREATE TABLE b_bp_tracking (
	ID int NOT NULL auto_increment,
	WORKFLOW_ID varchar(32) NOT NULL,
	TYPE int NOT NULL,
	MODIFIED datetime NOT NULL,
	ACTION_NAME varchar(128) NOT NULL,
	ACTION_TITLE varchar(255) NULL,
	EXECUTION_STATUS int NOT NULL default 0,
	EXECUTION_RESULT int NOT NULL default 0,
	ACTION_NOTE text NULL,
	MODIFIED_BY int NULL,
	primary key (ID),
	index ix_bp_tracking_wf(WORKFLOW_ID)
);

CREATE TABLE b_bp_task (
	ID int NOT NULL auto_increment,
	WORKFLOW_ID varchar(32) NOT NULL,
	ACTIVITY varchar(128) NOT NULL,
	ACTIVITY_NAME varchar(128) NOT NULL,
	MODIFIED datetime NOT NULL,
	OVERDUE_DATE datetime NULL,
	NAME varchar(128) NOT NULL,
	DESCRIPTION text NULL,
	PARAMETERS text NULL,
	primary key (ID),
	index ix_bp_tasks_sort(OVERDUE_DATE, MODIFIED),
	index ix_bp_tasks_wf(WORKFLOW_ID)
);

CREATE TABLE b_bp_task_user (
	ID int NOT NULL auto_increment,
	USER_ID int NOT NULL,
	TASK_ID int NOT NULL,
	primary key (ID),
	unique ix_bp_task_user(USER_ID, TASK_ID)
);

CREATE TABLE b_bp_history (
	ID int NOT NULL auto_increment,
	MODULE_ID varchar(32) NULL,
	ENTITY varchar(64) NOT NULL,
	DOCUMENT_ID varchar(128) NOT NULL,
	NAME varchar(255) NOT NULL,
	DOCUMENT blob NULL,
	MODIFIED datetime NOT NULL,
	USER_ID int NULL,
	primary key (ID),
	index ix_bp_history_doc(DOCUMENT_ID, ENTITY, MODULE_ID)
);

/*
	SELECT *
	FROM b_iblock_element e
	WHERE e.IBLOCK_ID = 5
		AND e.ID IN (
			SELECT intval(s.DOCUMENT_ID)
			FROM b_bp_workflow_state s
				INNER JOIN b_bp_workflow_permissions p ON (s.ID = p.WORKFLOW_ID)
			WHERE s.MODULE_ID = 'iblock'
				AND s.ENTITY = 'CIBlockDocument'
				AND p.PERMISSION = 'read'
				AND (intval(p.OBJECT_ID) IN ($USER->GetGroups())
					OR p.OBJECT_ID = 'Author' AND e.OWNER_ID = $USER->GetID()
					OR substr(p.OBJECT_ID, 0, strlen("USER_")) = "USER_" AND intval(substr(p.OBJECT_ID, strlen("USER_"))) = $USER->GetID()
				)
		)
*/
