<?php

namespace App\Providers;

use App\Validator\Validator;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\ServiceProvider;
use PagSeguro\Configuration\Configure;
use PagSeguro\Library;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     * @throws \Exception
     */
    public function boot()
    {
        $this->app['validator']->resolver(function ($translator, $data, $rules, $messages, $customAttributes) {
            $messages += [
                'cell_phone'  => 'O campo :attribute não é um possui o formato válido de celular com DDD',
                'cnpj'        => 'O campo :attribute não é um CNPJ válido',
                'cpf'         => 'O campo :attribute não é um CPF válido',
                'bool_custom' => 'O campo :attribute deve ser verdadeiro ou falso',
            ];

            return new Validator($translator, $data, $rules, $messages, $customAttributes);
        });

        $this->initialize();

        \Queue::before(function (JobProcessing $event) {
            $this->initialize();
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->environment() === 'local') $this->app->register(\Jenssegers\Mongodb\MongodbQueueServiceProvider::class);
    }

    /**
     * @throws \Exception
     */
    private function initialize()
    {
        Library::initialize();
        Library::cmsVersion()->setName('Nome')->setRelease('1.0.0');
        Library::moduleVersion()->setName('Nome')->setRelease('1.0.0');

        Configure::setEnvironment(config('app.env') === 'production' ? 'production' : 'sandbox');
        Configure::setAccountCredentials(config('pagseguro.email'), config('pagseguro.token'));
        Configure::setApplicationCredentials(config('pagseguro.app'), config('pagseguro.secret'));
        Configure::setCharset('UTF-8');
        Configure::setLog(TRUE, storage_path('logs/pagseguro/pagseguro-' . now()->format('Y-m-d') . '.log'));
    }
}
