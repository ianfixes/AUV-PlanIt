-- AUV PlanIt schema for topside PC
-- author: Ian Katz
-- last edited: 03/10


-- google earth altitude modes
drop table if exists altitudemode;

create table altitudemode (
    altitudemode_id int unsigned not null auto_increment,
    name varchar(255),

    primary key (altitudemode_id)
    );


-- trackable entities on map
drop table if exists entity;

create table entity (
    entity_id int unsigned not null auto_increment,
    altitudemode_id int unsigned not null,
    name varchar(255) not null,
    icon_img_url varchar(255),

    primary key (entity_id),
    foreign key (altitudemode_id) references altitudemode (altitudemode_id)
    );



-- locations of entities
drop table if exists entity_location;

create table entity_location (
    entity_id int unsigned not null,
    updated timestamp not null,
    lat double not null,
    lng double not null,
    alt double,
    heading double,

    primary key (entity_id, updated),
    foreign key (entity_id) references entity (entity_id)
    );


-- state variables from google earth
drop table if exists state;

create table state (
    state_id int unsigned not null auto_increment,
    name varchar(255) not null,

    primary key (state_id)
    );


-- user profiles (profiles)
drop table if exists profile;

create table profile (
    profile_id int unsigned not null auto_increment,
    name varchar(255) not null,
    enabled tinyint(1) not null,
    last_used timestamp,

    primary key (profile_id)
    );


-- state of current profile
drop table if exists profile_state;

create table profile_state (
    profile_id int unsigned not null,
    state_id int unsigned not null,
    value double not null,

    primary key (profile_id, state_id),
    foreign key (profile_id) references profile (profile_id),
    foreign key (state_id) references state (state_id)
    );


-- user settings
drop table if exists setting;

create table setting (
    setting_id int unsigned not null auto_increment,
    name varchar(255) not null,

    primary key (setting_id)
    );


-- instances of user settings
drop table if exists profile_setting;

create table profile_setting (
    profile_id int unsigned not null,
    setting_id int unsigned not null,
    value varchar(512) not null,

    primary key (profile_id, setting_id),
    foreign key (profile_id) references profile (profile_id),
    foreign key (setting_id) references setting (setting_id)
    );


-- types of mission
drop table if exists mission;

create table mission (
    mission_id int unsigned not null auto_increment,
    name varchar(255) not null,

    primary key (mission_id)
    );


-- parameters for mission primitives
-- no auto increment because these are tied to php classes
drop table if exists param;

create table param (
    param_id int unsigned not null,
    name varchar(255) not null,
    units varchar(25),
    unit_html varchar(25), -- shorthand units

    primary key (param_id)
    );

-- mission primitives
-- no auto increment because these are tied to php classes
drop table if exists primitive;

create table primitive (
    primitive_id int unsigned not null,
    name varchar(255) not null,

    primary key (primitive_id)
    );


-- parameters for primitives
drop table if exists primitive_param;

create table primitive_param (
    primitive_id int unsigned not null,
    param_id int unsigned not null,
    
    primary key (primitive_id, param_id),
    foreign key (primitive_id) references primitive (primitive_id),
    foreign key (param_id) references param (param_id)
    );

-- primitives needed to define a mission
drop table if exists mission_primitive;

create table mission_primitive (
    mission_primitive_id int unsigned not null auto_increment,
    mission_id int unsigned not null,
    primitive_id int unsigned not null,
    rank int unsigned not null,
    name varchar(255) not null,

    primary key (mission_primitive_id),
    foreign key (mission_id) references mission (mission_id),
    foreign key (primitive_id) references primitive (primitive_id)
    );


-- planned missions initiated by the user
drop table if exists plan;

create table plan (
    plan_id int unsigned not null auto_increment,
    profile_id int unsigned not null,
    mission_id int unsigned not null,
    name varchar(255) not null,
    when_updated datetime not null,
    last_exported datetime,
    hidden tinyint(1) not null,
    editing_rank int unsigned,

    primary key (plan_id),
    foreign key (profile_id) references profile (profile_id),
    foreign key (mission_id) references mission (mission_id)
--    foreign key (editing_primitive_id) references primitive (primitive_id)
    );


-- params added to planned mission
drop table if exists plan_param;

create table plan_param (
    plan_id int unsigned not null,
    mission_primitive_id int unsigned not null,
    param_id int unsigned not null,
    value varchar(1024),

    primary key (plan_id, mission_primitive_id, param_id),
    foreign key (plan_id) references plan (plan_id),
    foreign key (mission_primitive_id) references missionprimitive (mission_primitive_id),
    foreign key (param_id) references param (param_id)
    );

    


-- master view for plan data

create or replace view plan_data as
select plan.plan_id plan_id,
    plan.name plan_name,
    mission.mission_id mission_id,
    mission.name mission_name,
    mission_primitive.rank mission_primitive_rank,
    mission_primitive.mission_primitive_id mission_primitive_id,
    mission_primitive.name mission_primitive_name,
    primitive.primitive_id,
    primitive.name primitive_name,
    param.param_id param_id,
    param.name param_name,
    plan_param.value
from plan
    inner join mission using (mission_id)
    inner join mission_primitive on (mission_primitive.mission_id = mission.mission_id)
    inner join primitive on (mission_primitive.primitive_id = primitive.primitive_id)
    inner join primitive_param on (primitive_param.primitive_id = primitive.primitive_id) 
    inner join param using (param_id)
    left join plan_param on (
            plan_param.plan_id = plan.plan_id 
        and plan_param.mission_primitive_id = mission_primitive.mission_primitive_id 
        and plan_param.param_id = param.param_id);


