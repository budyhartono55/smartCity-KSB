<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;


class RedisHelper
{
    //agenda
    public static function deleteKeysAgenda()
    {      
        $keys = array_merge(
            Redis::keys('*agenda*'), 
            Redis::keys('*AllAgendas*'), 
            Redis::keys('*AllAgendasByUser*'), 
            Redis::keys('*admin*')
           );

        if (!empty($keys)) {
       $keys = array_map(fn($k) => str_replace(env('REDIS_KEY'), '', $k), $keys);
        Redis::del($keys);
        }
    }

    //category
    public static function deleteKeysCategory()
    {      
        $keys = array_merge(
            Redis::keys('*category*'), 
            Redis::keys('*AllCategories*'), 
            Redis::keys('*admin*')
           );

        if (!empty($keys)) {
        $keys = array_map(fn($k) => str_replace(env('REDIS_KEY'), '', $k), $keys);
        Redis::del($keys);
        }
    }

    //Gallery
    public static function deleteKeysGallery()
    {      
        $keys = array_merge(
            Redis::keys('*gallery*'), 
            Redis::keys('*AllGalleries*'), 
            Redis::keys('*admin*')
           );

        if (!empty($keys)) {
       $keys = array_map(fn($k) => str_replace(env('REDIS_KEY'), '', $k), $keys);
        Redis::del($keys);
        }
    }

    //service
    public static function deleteKeysService()
    {      
        $keys = array_merge(
                            Redis::keys('*service*'), 
                            Redis::keys('*AllServices*'), 
                            Redis::keys('*admin*')
                           );

        if (!empty($keys)) {
           $keys = array_map(fn($k) => str_replace(env('REDIS_KEY'), '', $k), $keys);
            Redis::del($keys);
        }
    }

    //pilar
    public static function deleteKeysPilar()
    {      
        $keys = array_merge(
                            Redis::keys('*pilar*'), 
                            Redis::keys('*AllPilars*'), 
                            Redis::keys('*AllPilars_withApp*'), 
                            Redis::keys('*admin*')
                          );
                          
        if (!empty($keys)) {
       $keys = array_map(fn($k) => str_replace(env('REDIS_KEY'), '', $k), $keys);
        Redis::del($keys);
        }
    }
    //application
    public static function deleteKeysApplication()
    {      
        $keys = array_merge(
                            Redis::keys('*application*'), 
                            Redis::keys('*AllApplications*'), 
                            Redis::keys('*admin*')
                          );
                          
        if (!empty($keys)) {
       $keys = array_map(fn($k) => str_replace(env('REDIS_KEY'), '', $k), $keys);
        Redis::del($keys);
        }
    }
    // Statistic
    public static function deleteKeysStatistic()
    {      
        $keys = array_merge(
                            Redis::keys('*statistic*'), 
                            Redis::keys('*AllStatistics*'), 
                            Redis::keys('*admin*')
                          );
                          
        if (!empty($keys)) {
       $keys = array_map(fn($k) => str_replace(env('REDIS_KEY'), '', $k), $keys);
        Redis::del($keys);
        }
    }
}