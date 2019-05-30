<?php

namespace Modules\Transaction\Console;

use Illuminate\Console\Command;
use Modules\Transaction\Repositories\TransactionRepository;
use Modules\Transaction\Services\TransactionService;

class CancelBoleto extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'boleto:cancel-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'It cancels expired boletos.';

    /**
     * @var \Modules\Transaction\Repositories\TransactionRepository
     */
    private $repository;

    /**
     * @var \Modules\Transaction\Services\TransactionService
     */
    private $service;

    /**
     * Create a new command instance.
     *
     * @param  \Modules\Transaction\Repositories\TransactionRepository  $repository
     * @param  \Modules\Transaction\Services\TransactionService  $service
     */
    public function __construct(TransactionRepository $repository, TransactionService $service)
    {
        parent::__construct();
        $this->repository = $repository;
        $this->service = $service;
    }

    /**
     * Execute the console command.
     *
     * @throws \Exception
     */
    public function handle()
    {
        $boletos = $this->repository->getExpiredBoletos();

        foreach ($boletos as $boleto) {
            $this->service->cancel($boleto->code);

            $this->repository->cancel($boleto->code);
        }
    }
}
