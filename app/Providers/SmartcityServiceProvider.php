<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class SmartcityServiceProvider extends ServiceProvider
{
    public function boot()
    {
    }

    public function register()
    {
        // S E R V I C E ================
        $this->app->bind(
            'App\Repositories\Service\ServiceInterface',
            'App\Repositories\Service\ServiceRepository'
        );
        // S T A T I S T I C ================
        $this->app->bind(
            'App\Repositories\Statistic\StatisticInterface',
            'App\Repositories\Statistic\StatisticRepository'
        );

        // S E T T I N G ================
        $this->app->bind(
            'App\Repositories\Setting\SettingInterface',
            'App\Repositories\Setting\SettingRepository'
        );

        // A G E N D A ================
        $this->app->bind(
            'App\Repositories\Agenda\AgendaInterface',
            'App\Repositories\Agenda\AgendaRepository'
        );

        // P I L A R ================
        $this->app->bind(
            'App\Repositories\Pilar\PilarInterface',
            'App\Repositories\Pilar\PilarRepository'
        );
        // A P P L I C A T I O N ================
        $this->app->bind(
            'App\Repositories\Application\ApplicationInterface',
            'App\Repositories\Application\ApplicationRepository'
        );

        // U S E R  ================
        $this->app->bind(
            'App\Repositories\User\UserInterface',
            'App\Repositories\User\UserRepository'
        );

        // C A T E G O R Y ================
        $this->app->bind(
            'App\Repositories\Category\CategoryInterface',
            'App\Repositories\Category\CategoryRepository'
        );

        // N E W S ================
        $this->app->bind(
            'App\Repositories\News\NewsInterface',
            'App\Repositories\News\NewsRepository'
        );

        // C O M M E N T S ================
        $this->app->bind(
            'App\Repositories\Comment\CommentInterface',
            'App\Repositories\Comment\CommentRepository'
        );

        // G A L L E R Y ================
        $this->app->bind(
            'App\Repositories\Gallery\GalleryInterface',
            'App\Repositories\Gallery\GalleryRepository'
        );

        // C O N T A C T ================
        $this->app->bind(
            'App\Repositories\Contact\ContactInterface',
            'App\Repositories\Contact\ContactRepository'
        );

        // A U T H ================
        $this->app->bind(
            'App\Repositories\Auth\AuthInterface',
            'App\Repositories\Auth\AuthRepository'
        );
        // A D M I N ================
        $this->app->bind(
            'App\Repositories\admin\Dashboard\DashboardInterface',
            'App\Repositories\admin\Dashboard\DashboardRepository',
        );

        // R E G U L A S I ================
        $this->app->bind(
            'App\Repositories\Regulasi\RegulasiInterface',
            'App\Repositories\Regulasi\RegulasiRepository'
        );

        // P R O F I L E ================
        $this->app->bind(
            'App\Repositories\Profile\ProfileInterface',
            'App\Repositories\Profile\ProfileRepository'
        );

        // P R O F I L E ================
        $this->app->bind(
            'App\Repositories\Infrastruktur\InfrastrukturInterface',
            'App\Repositories\Infrastruktur\InfrastrukturRepository'
        );

        // S L I D E R================
        $this->app->bind(
            'App\Repositories\Slider\SliderInterface',
            'App\Repositories\Slider\SliderRepository'
        );
    }
}