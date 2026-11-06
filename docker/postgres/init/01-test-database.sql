-- Separate database for the Pest suite so tests never touch dev data.
CREATE DATABASE tomato_lane_test OWNER tomato_lane ENCODING 'UTF8' LOCALE 'C.UTF-8' TEMPLATE template0;
