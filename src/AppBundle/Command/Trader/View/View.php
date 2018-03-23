<?php
declare(strict_types=1);

namespace AppBundle\Command\Trader\View;
use AppBundle\Command\Trader\App;
use AppBundle\Command\Trader\Trade\Trade;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

/**
 * Class View
 * @package AppBundle\Command\Trader\View
 */
class View {

    /** @var InputInterface */
    private $input;

    /** @var OutputInterface */
    private $output;

    /** @var SymfonyStyle */
    private $io;

    /**
     * @return InputInterface
     */
    public function getInput(): InputInterface
    {
        return $this->input;
    }

    /**
     * @param InputInterface $input
     */
    public function setInput(InputInterface $input): void
    {
        $this->input = $input;
    }

    /**
     * @return OutputInterface
     */
    public function getOutput(): OutputInterface
    {
        return $this->output;
    }

    /**
     * @param OutputInterface $output
     */
    public function setOutput(OutputInterface $output): void
    {
        $this->output = $output;
    }

    /**
     * @return SymfonyStyle
     */
    public function getIo(): SymfonyStyle
    {
        return $this->io;
    }

    /**
     * @param SymfonyStyle $io
     */
    public function setIo(SymfonyStyle $io): void
    {
        $this->io = $io;
    }

    /**
     * @param $dots
     * @return string
     */
    public function paintCertainty($dots)
    {
        $string='';
        for(;$dots>0;$dots--) $string.="+";
        return $string;
    }

    /**
     * View constructor.
     * @param $input
     * @param $output
     */
    public function __construct($input, $output)
    {
        $this->output = $output;
        $this->input = $input;
        $this->io = new SymfonyStyle($input, $output);
    }

    /**
     * @param App $app
     * @param Trade $trade
     */
    public function renderTradingView(App $app, Trade $trade) {
        $this->getOutput()->write(sprintf("\033\143"));
        $this->getIo()->title('[JiNK] Client'.(!$app->isProduction()?' [dev]':''));

        $this->getIo()->section('Application settings:');
        $this->getIo()->text('Profit limit: '.$trade->getLimit()->getProfit().'%');
        $this->getIo()->text('Dump limit:   '.$trade->getLimit()->getDump().'% [Triggers only with positive profit]');
        $this->getIo()->text('Loss limit:   '.$trade->getLimit()->getLoss().'%');
        $this->getIo()->text('Time limit:   '.$trade->getLimit()->getTime().'min');
        $this->getIo()->newLine(1);

        $this->getIo()->section('Token: '.$trade->getToken().'/'.$trade->getBasicToken().' ['.$trade->getBuyTokenAmount().' '.$trade->getToken().']');

        $this->getIo()->text('Your price:    '.sprintf('%.8f', $trade->getPrice()->getBuy()));
        if ($trade->getPrice()->getCurrent() >= $trade->getPrice()->getLast()) {
            $this->getIo()->text('Current price: <fg=green>'.sprintf('%.8f', $trade->getPrice()->getCurrent()).'</>');
        } else {
            $this->getIo()->text('Current price: <fg=red>'.sprintf('%.8f', $trade->getPrice()->getCurrent()).'</>');
        }
        $this->getIo()->text('Maximum price: '.sprintf('%.8f',$trade->getPrice()->getMax()));
        $this->getIo()->newLine(1);

        if ($trade->getCurrent()->getProfit() >= 0) {
            $this->getIo()->text('Current profit: <fg=green> '.$trade->getCurrent()->getProfit().'% </> '.$this->paintCertainty($trade->getCertainty()->getProfit()));
        } else {
            $this->getIo()->text('Current profit: <fg=red> '.$trade->getCurrent()->getProfit().'% </> '.$this->paintCertainty($trade->getCertainty()->getLoss()));
        }

        $this->getIo()->text('Current dump:   <fg=red> '.$trade->getCurrent()->getDump().'% </> '.$this->paintCertainty($trade->getCertainty()->getDump()));
        $this->getIo()->newLine(2);

    }

    /**
     * @param App $app
     */
    public function renderClientView(App $app) {
        $this->getOutput()->write(sprintf("\033\143"));
        $this->getIo()->title('[JiNK] Client'.(!$app->isProduction()?' [dev]':''));
        if ($app->isTrading()) {
            $this->renderClientViewTrading($app);
        } else {
            $this->renderClientViewWaiting($app);
        }
    }

    /**
     * @param App $app
     */
    public function renderClientViewTrading(App $app) {
        /** @var Process $process */
        foreach ($app->getProcesses() as $process) {
            $this->getIo()->text('Process ID '.$process->getPid().' is running ...');
        }
    }

    /**
     * @param App $app
     */
    public function renderClientViewWaiting(App $app) {
        $this->getIo()->section('Waiting for signal...');
    }
}