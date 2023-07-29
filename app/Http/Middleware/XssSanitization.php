<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use HTMLPurifier_Config;
use HTMLPurifier;

class XssSanitization
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, $allowField = "")
    {
        if ($allowField !== "") {
            // $fieldExcept = explode("|", $allowField);
            $allowFieldList = [
                'description',
                'caption',
                'strategy',
                'program',
                'pengembangan_kebijakan_dan_kelembagaan',
                'infrastruktur_pendukung',
                'penguatan_literasi'
            ];
            $input = $request->except($allowFieldList);
            // $input = $request->except($fieldExcept);
            // dd($request->only($fieldExcept));
            array_walk_recursive($input, function (&$input) {
                $input = strip_tags($input);
            });
            $resultExcept = $request->merge($input);
            $inputOnly = $resultExcept->only($allowFieldList);
            // dd($inputResult);
            array_walk_recursive($inputOnly, function (&$inputOnly) {
                // $inputOnly = strip_tags($inputOnly, '<script><h1><h2><p><strong><em>');
                $config =  HTMLPurifier_Config::createDefault();
                $listed = 'div,br,ol,li,ul,a,span,h1,h2,h3,h4,h5,h6,p,strong,em,table,tr,th,td,code,blockquote,s,u';
                $config->set('HTML.Allowed', $listed);
                $purifier = new HTMLPurifier($config);
                $inputOnly =  $purifier->purify($inputOnly);
            });
            // dd($inputResult);
            $request->merge($inputOnly, $input);
            // return $next($request);
        } else {
            $input = $request->all();
            array_walk_recursive($input, function (&$input) {
                $input = strip_tags($input);
            });
            $request->merge($input);
            // return $next($request);
        }
        return $next($request);
    }
}
