<?php

namespace App\Command;

use App\Service\Twig;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use App\Repository\TransactionRepository;

class CourseCommand extends Command
{
    private $twig;
    private $mailer;
    private $transRepo;

    public function __construct(
        Twig $twig,
        MailerInterface $mailer,
        TransactionRepository $transactionRepository
    ){
        $this->twig = $twig;
        $this->mailer = $mailer;
        $this->transRepo = $transactionRepository;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('payment:ending:notification')
            ->setDescription('Уведомление об окончании срока аренды.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $qb = $this->transRepo->createQueryBuilder('t')
            ->where('t.payTime - :current_time < \'23:59:59\' AND t.payTime > :current_time')
            ->setParameter(':current_time', new \DateTime());

        $query = $qb->getQuery();
        $transactions = $query->execute();

        $messagesForUser = [];

        foreach($transactions as $transaction){
            $email = $transaction->getBillingUser()->getEmail();
            $courseName = $transaction->getCourse()->getTitle();
            $payTime = $transaction->getPayTime();

            $messagesForUser[$email][] = [
                "courseName" => $courseName,
                "payTime" => $payTime
            ];
        }

        foreach($messagesForUser as $email => $data){
            $this->sendEmail($email, $data);
        }


        return 0;
    }

    protected function sendEmail($route, $data){

        $html = $this->twig->render(
            'email/course.html.twig',
            [
                "data" => $data
            ]
        );

        $email = (new Email())
            ->from('study-on@study.com')
            ->to($route)
            ->html($html, 'text/html');

        $this->mailer->send($email);
    }



}