<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

---

| No. | Method | EndPoint                     | Description                                       |
| --- | ------ | ---------------------------- | ------------------------------------------------- |
| 1   | GET    | api/services                 | Get All services                                  |
| 2   | GET    | api/service/{id}             | Get services by/{id}                              |
| 3   | POST   | api/service                  | Insert Service                                    |
| 4   | PATCH  | api/service/{id}             | Update Service by/{id}                            |
| 5   | DELETE | api/service                  | Delete Service by/{id}                            |
| 6   | GET    | api/categories               | Get All Categories                                |
| 7   | GET    | api/category/{id}            | Get Category by/{id}                              |
| 8   | POST   | api/category                 | Insert category                                   |
| 9   | PATCH  | api/category/{id}            | Update category by/{id}                           |
| 10  | DELETE | api/category/{id}            | Delete Category by/{id}                           |
| 11  | GET    | api/pilars                   | Get All Pilars                                    |
| 12  | GET    | api/pilar/{id}               | Get Pilar by/{id}                                 |
| 13  | POST   | api/pilar                    | Insert Pilar                                      |
| 14  | PATCH  | api/pilar/{id}               | Update Pilar by/{id}                              |
| 15  | DELETE | api/pilar/{id}               | Delete Pilar by/{id}                              |
| 16  | GET    | api/news                     | Get All News                                      |
| 17  | GET    | api/news/{id}                | Get News by/{id}                                  |
| 18  | GET    | api/news/search/{keyword}    | Get News by/{keyword}                             |
| 19  | GET    | api/news/category/{id}       | Get All News by/{id} Category                     |
| 20  | POST   | api/news                     | Insert News                                       |
| 21  | PATCH  | api/news/{id}                | Update News by/{id}                               |
| 22  | DELETE | api/news/{id}                | Delete News by/{id}                               |
| 23  | GET    | api/comment                  | Get All Comment                                   |
| 24  | GET    | api/comment/{id}             | Get Comment by/{id}                               |
| 25  | POST   | api/comment                  | Insert Comment                                    |
| 26  | PATCH  | api/comment/{id}             | Update Comment by/{id}                            |
| 27  | DELETE | api/comment/{id}             | Delete Comment by/{id}                            |
| 28  | GET    | api/setting                  | Get All Setting                                   |
| 29  | GET    | api/setting/{id}             | Get Setting by/{id}                               |
| 30  | POST   | api/setting                  | Insert Setting                                    |
| 31  | PATCH  | api/setting/{id}             | Update Setting by/{id}                            |
| 32  | DELETE | api/setting/{id}             | Delete Setting by/{id}                            |
| 33  | GET    | api/user                     | Get All User                                      |
| 34  | GET    | api/user/{id}                | Get User by/{id}                                  |
| 35  | POST   | api/user                     | Insert User                                       |
| 36  | PATCH  | api/user/{id}                | Update User by/{id}                               |
| 37  | DELETE | api/user/{id}                | Delete User by/{id}                               |
| 38  | POST   | api/register                 | Register User                                     |
| 39  | POST   | api/login                    | Login To Get Token                                |
| 40  | GET    | api/galleries                | Get All Galleries                                 |
| 41  | GET    | api/gallery/{id}             | Get Gallery by/{id}                               |
| 42  | POST   | api/gallery                  | Insert Gallery                                    |
| 43  | PATCH  | api/gallery/{id}             | Update Gallery by/{id}                            |
| 44  | DELETE | api/gallery/{id}             | Delete Gallery by/{id}                            |
| 45  | GET    | api/agendas                  | Get All Galleries                                 |
| 46  | GET    | api/agenda/{id}              | Get Agenda by/{id}                                |
| 47  | GET    | api/agenda/category/{id}     | Get All Agenda by/{id} Category                   |
| 48  | POST   | api/agenda                   | Insert Agenda                                     |
| 49  | PATCH  | api/agenda/{id}              | Update Agenda by/{id}                             |
| 50  | DELETE | api/agenda/{id}              | Delete Agenda by/{id}                             |
| 51  | GET    | api/agenda/search/{keyword}  | Get all Agenda by/{keyword}                       |
| 52  | GET    | api/agenda/read/{slug}       | Get Agenda by/{slug}                              |
| 53  | GET    | api/service/search/{keyword} | Get all service by/{keyword}                      |
| 54  | POST   | api/changePassword           | changePassword User                               |
| 55  | POST   | api/forgotPassword           | sent OTP verification for resetting password User |
| 56  | POST   | api/resetPassword            | resetPassword User                                |
| 57  | GET    | api/admin/datasCount         | getAll count data                                 |
| 58  | GET    | api/applications             | Get All Pilars                                    |
| 59  | GET    | api/applications/{id}        | Get Pilar by/{id}                                 |
| 61  | POST   | api/applications             | Insert Pilar                                      |
| 62  | PATCH  | api/applications/{id}        | Update Pilar by/{id}                              |
| 63  | DELETE | api/applications/{id}        | Delete Pilar by/{id}                              |
| 64  | GET    | api//pilarsIncludeApp        | Get All Dimensi include App                       |

---

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

-   [Simple, fast routing engine](https://laravel.com/docs/routing).
-   [Powerful dependency injection container](https://laravel.com/docs/container).
-   Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
-   Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
-   Database agnostic [schema migrations](https://laravel.com/docs/migrations).
-   [Robust background job processing](https://laravel.com/docs/queues).
-   [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains over 2000 video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the Laravel [Patreon page](https://patreon.com/taylorotwell).

### Premium Partners

-   **[Vehikl](https://vehikl.com/)**
-   **[Tighten Co.](https://tighten.co)**
-   **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
-   **[64 Robots](https://64robots.com)**
-   **[Cubet Techno Labs](https://cubettech.com)**
-   **[Cyber-Duck](https://cyber-duck.co.uk)**
-   **[Many](https://www.many.co.uk)**
-   **[Webdock, Fast VPS Hosting](https://www.webdock.io/en)**
-   **[DevSquad](https://devsquad.com)**
-   **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
-   **[OP.GG](https://op.gg)**
-   **[WebReinvent](https://webreinvent.com/?utm_source=laravel&utm_medium=github&utm_campaign=patreon-sponsors)**
-   **[Lendio](https://lendio.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
