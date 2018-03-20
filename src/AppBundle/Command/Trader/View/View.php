<?php
declare(strict_types=1);

namespace AppBundle\Command\Trader\View;
use AppBundle\Command\Trader\App;
use AppBundle\Command\Trader\Trade\Trade;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

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
     */
    public function renderView(App $app) {
        $this->getOutput()->write(sprintf("\033\143"));
        $this->getIo()->title('[JiNK] Client'.(!$app->isProduction()?' [dev]':''));
        if ($app->isTrading()) {
            $this->renderViewTrading($app);
        } else {
            $this->renderViewWaiting($app);
        }
    }

    /**
     * @param App $app
     */
    public function renderViewTrading(App $app) {

        $this->getIo()->section('Application settings:');
        $this->getIo()->text('Profit limit: '.$app->getLimit()->getProfit().'%');
        $this->getIo()->text('Dump limit:   '.$app->getLimit()->getDump().'% [Triggers only with positive profit]');
        $this->getIo()->text('Loss limit:   '.$app->getLimit()->getLoss().'%');
        $this->getIo()->text('Time limit:   '.$app->getLimit()->getTime().'min');
        $this->getIo()->newLine(1);

        /** @var Trade $trade */
        foreach ($app->getTrades() as $trade) {
            $this->getIo()->section('Token: '.$trade->getToken().'/'.$trade->getBasicToken().' ['.$trade->getBuyTokenAmount().' '.$trade->getToken().']');

            $this->getIo()->text('Your price:    '.sprintf('%.8f', $trade->getPrice()->getBuy()));
            if ($trade->getPrice()->getCurrent() >= $trade->getPrice()->getLast()) {
                $this->getIo()->text('Current price: <fg=green>'.sprintf('%.8f', $trade->getPrice()->getCurrent()).'</>');
            } else {
                $this->getIo()->text('Current price: <fg=red>'.sprintf('%.8f', $trade->getPrice()->getCurrent()).'</>');
            }
            $this->getIo()->text('Maximum price: '.sprintf('%.8f',$trade->getPrice()->getMax()));
            $this->getIo()->newLine(1);

            if ($trade->getState() === Trade::STATE_OPEN) {
                if ($trade->getCurrent()->getProfit() >= 0) {
                    $this->getIo()->text('Current profit: <fg=green> '.$trade->getCurrent()->getProfit().'% </> '.$this->paintCertainty($trade->getCertainty()->getProfit()));
                } else {
                    $this->getIo()->text('Current profit: <fg=red> '.$trade->getCurrent()->getProfit().'% </> '.$this->paintCertainty($trade->getCertainty()->getLoss()));
                }

                $this->getIo()->text('Current dump:   <fg=red> '.$trade->getCurrent()->getDump().'% </> '.$this->paintCertainty($trade->getCertainty()->getDump()));
                $this->getIo()->newLine(2);

            } elseif ($trade->getState() === Trade::STATE_CLOSED) {
                $this->getIo()->success('Sold with '.$trade->getCurrent()->getProfit().'% profit [Dump: '.$trade->getCurrent()->getDump().'%]');
            } else {
                $this->getIo()->error('Error with pair '.$trade->getBasicToken().'/'.$trade->getToken());
            }

        }
    }

    /**
     * @param App $app
     */
    public function renderViewWaiting(App $app) {
        $this->getIo()->section('Waiting for signal...');
    }
}