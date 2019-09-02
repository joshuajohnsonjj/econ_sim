<?php

// The SQL to uninstall this tool
$DATABASE_UNINSTALL = array(
    // Nothing
);

// The SQL to create the tables if they don't exist
$DATABASE_INSTALL = array(
    array( "{$CFG->dbprefix}courses",
        "create table {$CFG->dbprefix}courses (
    id INT(6) UNSIGNED AUTO_INCREMENT,
    name VARCHAR(30) NOT NULL,
    section VARCHAR(30) NOT NULL,
    owner VARCHAR(30) NOT NULL,
    avatar VARCHAR(30) DEFAULT 'fa-chart-bar',
    reg_date TIMESTAMP,
    
    PRIMARY KEY(id)
	
) ENGINE = InnoDB DEFAULT CHARSET=utf8"),
    array( "{$CFG->dbprefix}games",
        "create table {$CFG->dbprefix}games (
    id INT(6) UNSIGNED AUTO_INCREMENT,
    name VARCHAR(30) NOT NULL,
    live BOOLEAN DEFAULT FALSE,
    type VARCHAR(30) NOT NULL,
    course_id VARCHAR(30) NOT NULL,
    difficulty VARCHAR(30) NOT NULL,
    mode VARCHAR(30) NOT NULL,
    market_struct VARCHAR(30) NOT NULL,
    macro_econ  VARCHAR(30) NOT NULL,
    rand_events BOOLEAN NOT NULL,
    time_limit INT(6) NOT NULL,
    num_rounds INT(6) NOT NULL,
    demand_intercept INT(6) NOT NULL,
    demand_slope INT(6) NOT NULL,
    fixed_cost INT(6) NOT NULL,
    const_cost INT(6) NOT NULL,
    equilibrium INT(6) DEFAULT NULL,
    price_hist     VARCHAR(300)    DEFAULT NULL,
    reg_date TIMESTAMP,
    
    PRIMARY KEY(id)
	
) ENGINE = InnoDB DEFAULT CHARSET=utf8"),    
    array( "{$CFG->dbprefix}gameSessionData",
        "create table {$CFG->dbprefix}gameSessionData (
    id                  INT(6)          UNSIGNED AUTO_INCREMENT,
    complete            BOOLEAN         DEFAULT FALSE,
    groupId             VARCHAR(10)     NOT NULL,
    player              VARCHAR(30)     NOT NULL,
    opponent            VARCHAR(30)     DEFAULT NULL,
    player_quantity     VARCHAR(300)    NOT NULL,
    player_profit       VARCHAR(300)    NOT NULL,
    player_revenue      VARCHAR(300)    NOT NULL,
    player_return       VARCHAR(300)    NOT NULL,
    price               VARCHAR(300)    NOT NULL,
    unit_cost           VARCHAR(300)    NOT NULL,
    total_cost          VARCHAR(300)    NOT NULL,

    PRIMARY KEY(id)
	
) ENGINE = InnoDB DEFAULT CHARSET=utf8"),
    array( "{$CFG->dbprefix}sessions",
        "create table {$CFG->dbprefix}sessions (
    id                  INT(6)          UNSIGNED AUTO_INCREMENT,
    groupId             VARCHAR(10)     NOT NULL,
    gameId              Int(6)          NOT NULL,
    p1                  VARCHAR(30)     DEFAULT NULL,
    p1Data              INT(20)         DEFAULT NULL,
    
    PRIMARY KEY(id)
    
) ENGINE = InnoDB DEFAULT CHARSET=utf8")
);
