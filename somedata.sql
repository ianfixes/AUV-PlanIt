-- initial SQL data for Ian's AUV PlanIt

insert into altitudemode(name) values('absolute');
insert into altitudemode(name) values('relativeToGround');
insert into altitudemode(name) values('relativeToSeaFloor');
insert into altitudemode(name) values('clampToSeaFloor');
insert into altitudemode(name) values('clampToGround');

insert into state(name) values('bboxWest');
insert into state(name) values('bboxSouth');
insert into state(name) values('bboxEast');
insert into state(name) values('bboxNorth');
insert into state(name) values('lookatLon');
insert into state(name) values('lookatLat');
insert into state(name) values('lookatRange');
insert into state(name) values('lookatTilt');
insert into state(name) values('lookatHeading');
insert into state(name) values('lookatTerrainLon');
insert into state(name) values('lookatTerrainLat');
insert into state(name) values('lookatTerrainAlt');
insert into state(name) values('cameraLon');
insert into state(name) values('cameraLat');
insert into state(name) values('cameraAlt');
insert into state(name) values('horizFov');
insert into state(name) values('vertFov');
insert into state(name) values('horizPixels');
insert into state(name) values('vertPixels');
insert into state(name) values('terrainEnabled');

insert into setting(setting_id, name) values(1, 'Reticle');
insert into setting(setting_id, name) values(2, 'Box Color');
insert into setting(setting_id, name) values(3, 'Entity History (minutes)');
insert into setting(setting_id, name) values(4, 'Origin Latitude');
insert into setting(setting_id, name) values(5, 'Origin Longitude');
insert into setting(setting_id, name) values(6, 'Mission Copy Command');
-- insert into setting(setting_id, name) values(3, '');

insert into profile(name, enabled, last_used) values ('Ian', 1, now());

-- manual numbering because PHP classes will depend on these
insert into param(param_id, name)                   values(1, 'Thrust ratio');
insert into param(param_id, name)                   values(2, 'Box dimensions');
insert into param(param_id, name, units, unit_html) values(3, 'Latitude', 'degrees', '&deg;');
insert into param(param_id, name, units, unit_html) values(4, 'Longitude', 'degrees', '&deg;');
insert into param(param_id, name, units, unit_html) values(5, 'Altitude', 'meters', 'm');
insert into param(param_id, name, units, unit_html) values(6, 'Depth', 'meters', 'm');
insert into param(param_id, name, units, unit_html) values(7, 'Heading', 'degrees', '&deg;');
insert into param(param_id, name, units, unit_html) values(8, 'Timeout', 'seconds', 's');
insert into param(param_id, name, units, unit_html) values(9, 'Trackline spacing', 'meters', 'm');

-- manual numbering because PHP classes will depend on these
insert into primitive(primitive_id, name) values(1, 'Altitude');
insert into primitive(primitive_id, name) values(2, 'Depth');
insert into primitive(primitive_id, name) values(3, 'Waypoint');
insert into primitive(primitive_id, name) values(4, 'WaypointAltitude');
insert into primitive(primitive_id, name) values(5, 'WaypointDepth');
insert into primitive(primitive_id, name) values(6, 'SurveyAltitude');
insert into primitive(primitive_id, name) values(7, 'SurveyDepth');
insert into primitive(primitive_id, name) values(8, 'ConstantHeading');
-- insert into primitive(primitive_id, name) values(, '');


-- missions
insert into mission(mission_id, name) values(1,  'Dive to Altitude');
insert into mission(mission_id, name) values(2,  'Dive to Depth');
insert into mission(mission_id, name) values(3,  'Surface Waypoint x1');
insert into mission(mission_id, name) values(4,  'Surface Waypoint x2');
insert into mission(mission_id, name) values(5,  'Surface Waypoint x3');
insert into mission(mission_id, name) values(6,  'Trackline Altitude Waypoint x1');
insert into mission(mission_id, name) values(7,  'Trackline Altitude Waypoint x2');
insert into mission(mission_id, name) values(8,  'Trackline Depth Waypoint x1');
insert into mission(mission_id, name) values(9,  'Trackline Depth Waypoint x2');
-- insert into mission(mission_id, name) values(, '');

-- define missions
insert into mission_primitive(mission_id, primitive_id, name, rank) values(1, 1, 'Maintain Alititude', 1);

insert into mission_primitive(mission_id, primitive_id, name, rank) values(2, 2, 'Maintain Depth', 1);

insert into mission_primitive(mission_id, primitive_id, name, rank) values(3, 3, 'Go Somewhere', 1);

insert into mission_primitive(mission_id, primitive_id, name, rank) values(4, 3, 'Go Somewhere', 1);
insert into mission_primitive(mission_id, primitive_id, name, rank) values(4, 3, 'Go Somwhere Else', 2);

insert into mission_primitive(mission_id, primitive_id, name, rank) values(5, 3, 'Waypoint 1', 1);
insert into mission_primitive(mission_id, primitive_id, name, rank) values(5, 3, 'Waypoint 2', 2);
insert into mission_primitive(mission_id, primitive_id, name, rank) values(5, 3, 'Waypoint 3', 3);

insert into mission_primitive(mission_id, primitive_id, name, rank) values(6, 1, 'Go to Altitude', 1);
insert into mission_primitive(mission_id, primitive_id, name, rank) values(6, 4, 'Go to Waypoint', 2);

insert into mission_primitive(mission_id, primitive_id, name, rank) values(7, 1, 'Go to Altitude', 1);
insert into mission_primitive(mission_id, primitive_id, name, rank) values(7, 4, 'Go to Waypoint 1', 2);
insert into mission_primitive(mission_id, primitive_id, name, rank) values(7, 4, 'Go to Waypoint 2', 3);

insert into mission_primitive(mission_id, primitive_id, name, rank) values(8, 2, 'Go to Depth', 1);
insert into mission_primitive(mission_id, primitive_id, name, rank) values(8, 5, 'Go to Waypoint', 2);

insert into mission_primitive(mission_id, primitive_id, name, rank) values(9, 2, 'Go to Depth', 1);
insert into mission_primitive(mission_id, primitive_id, name, rank) values(9, 5, 'Go to Waypoint 1', 2);
insert into mission_primitive(mission_id, primitive_id, name, rank) values(9, 5, 'Go to Waypoint 2', 3);

-- insert into mission_primitive(mission_id, primitive_id, name, rank) values(, , '', );


-- define primitives
insert into primitive_param(primitive_id, param_id) values(1, 1);
insert into primitive_param(primitive_id, param_id) values(1, 5);
insert into primitive_param(primitive_id, param_id) values(1, 8);

insert into primitive_param(primitive_id, param_id) values(2, 1);
insert into primitive_param(primitive_id, param_id) values(2, 6);
insert into primitive_param(primitive_id, param_id) values(2, 8);

insert into primitive_param(primitive_id, param_id) values(3, 1);
insert into primitive_param(primitive_id, param_id) values(3, 3);
insert into primitive_param(primitive_id, param_id) values(3, 4);

insert into primitive_param(primitive_id, param_id) values(4, 1);
insert into primitive_param(primitive_id, param_id) values(4, 3);
insert into primitive_param(primitive_id, param_id) values(4, 4);
insert into primitive_param(primitive_id, param_id) values(4, 5);

insert into primitive_param(primitive_id, param_id) values(5, 1);
insert into primitive_param(primitive_id, param_id) values(5, 3);
insert into primitive_param(primitive_id, param_id) values(5, 4);
insert into primitive_param(primitive_id, param_id) values(5, 6);

insert into primitive_param(primitive_id, param_id) values(6, 1);
insert into primitive_param(primitive_id, param_id) values(6, 2);
insert into primitive_param(primitive_id, param_id) values(6, 3);
insert into primitive_param(primitive_id, param_id) values(6, 4);
insert into primitive_param(primitive_id, param_id) values(6, 5);
insert into primitive_param(primitive_id, param_id) values(6, 7);
insert into primitive_param(primitive_id, param_id) values(6, 9);

insert into primitive_param(primitive_id, param_id) values(7, 1);
insert into primitive_param(primitive_id, param_id) values(7, 2);
insert into primitive_param(primitive_id, param_id) values(7, 3);
insert into primitive_param(primitive_id, param_id) values(7, 4);
insert into primitive_param(primitive_id, param_id) values(7, 6);
insert into primitive_param(primitive_id, param_id) values(7, 7);
insert into primitive_param(primitive_id, param_id) values(7, 9);

insert into primitive_param(primitive_id, param_id) values(8, 7);
insert into primitive_param(primitive_id, param_id) values(8, 8);

-- insert into primitive_param(primitive_id, param_id) values(, );

