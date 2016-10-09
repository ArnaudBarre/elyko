# ELYKO: Establish a List of Your Known Outcomes
## Context
The interface to display student’s marks in my school, named Oasis, is very slow because the database contains thousands of data from different things (timetable, internships, …) for the ten last years. Also, the interface is not responsive, and marks are displayed only when you clicked on a course (UV: unit of value). To check if he has a new mark, a student should click on all courses (about ten) of the current semester.
The project was made with [ss1993] (https://github.com/ss1993) in May 2016.
## Frameworks
We chose to use [AngularJS] (https://github.com/angular/angular.js) in front end and [Lumen] (https://github.com/laravel/lumen) in back end.
## Back end
#### New database
To improve the speed, we chose to create a new database (MySQL) that will contain only the marks of current students. The first step was to create this DB using Eloquent migrations and then implemented the seeder to test the front end with some fake data.
#### Migration
Then I created a [script] (https://github.com/ArnaudBarre/elyko/blob/master/database/OasisToElyko.php) to get marks from Oasis DB (my school provided me a view of this DB (MsSQL), on the school server). This script is run regularly to update marks in the new DB (about every hour).
#### Controllers
I defined some routes and controllers to send data in JSON format.
#### Hosting and login
The back end is host on the school server, and use the school login. So students used the same login that in other websites of the school, and so I don’t have access to passwords.
## Front end by [ss1993] (https://github.com/ss1993)
A picture is worth a thousand words
![Sreenshot](https://raw.githubusercontent.com/ArnaudBarre/elyko/master/public/screenshot.png)
