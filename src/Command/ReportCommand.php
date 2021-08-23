<?php

namespace App\Command;

use App\Service\Twig;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use App\Repository\TransactionRepository;

class ReportCommand extends Command
{
    private $twig;
    private $mailer;
    private $transRepo;
    private $entityManager;

    public function __construct(
        Twig $twig,
        MailerInterface $mailer,
        TransactionRepository $transactionRepository,
        EntityManagerInterface $entityManager
    ){
        $this->twig = $twig;
        $this->mailer = $mailer;
        $this->transRepo = $transactionRepository;
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('payment:report')
            ->setDescription('Отчет по данным об оплаченных курсах за месяц.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $qb = $this->transRepo->createQueryBuilder('t')
            ->where('t.createdAt > :current_time')
            ->setParameter(':current_time', (new \DateTime())->modify('-1 month'));

        $query = $qb->getQuery();
        $transactions = $query->execute();

        $messagesForUser = [];

        foreach($transactions as $transaction){
            $user = $transaction->getBillingUser();
            $course = $transaction->getCourse();
            $courseId = $course->getId();

            $email = $user->getEmail();
            if(array_key_exists($email, $messagesForUser) && array_key_exists($courseId, $messagesForUser[$email])){
                continue;
            }

            $qb = $this->transRepo->createQueryBuilder('t')
                ->where('t.createdAt > :current_time')
                ->setParameter(':current_time', (new \DateTime())->modify('-1 month'))
                ->andWhere('t.billing_user = :user AND t.course = :course')
                ->setParameter('user', $user)
                ->setParameter('course', $course);
            $query = $qb->getQuery();
            $transactionsForUser = $query->execute();

            $countBuy = count($transactionsForUser);
            $sum = 0;

            foreach($transactionsForUser as $transForUser){
                $sum += $transForUser->getValue();
            }

            $courseName = $course->getTitle();
            $type = $course->getType() == 1 ? "Покупка" : "Аренда";



            $messagesForUser[$email][$courseId][] = [
                "courseName" => $courseName,
                "type" => $type,
                "countBuy" => $countBuy,
                "sum" => $sum,
            ];
        }

        foreach($messagesForUser as $email => $data){
            $data["startDate"] = (new \DateTime())->modify('-1 month');
            $data["endDate"] = new \DateTime();

            $this->sendEmail($email, $data);
        }


        return 0;
    }

    protected function sendEmail($route, $data){

        $html = $this->twig->render(
            'email/report.html.twig',
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