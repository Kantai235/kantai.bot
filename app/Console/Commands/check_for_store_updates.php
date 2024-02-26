<?php

namespace App\Console\Commands;

use App\Domains\Bahamut\Repositories\ProductRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class check_for_store_updates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check_for_store_updates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     */
    public function __construct(
        protected ProductRepository $productRepository
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $response = Http::withCookies([
            'ckBUY_item18UP' => '18UP',
        ], 'buy.gamer.com.tw')->get('https://buy.gamer.com.tw/indexList.php?gc1=998');

        $htmlContent = $response->body();

        $crawler = new Crawler($htmlContent);

        $products = $crawler->filter('.products-cards-area .products-card')->each(function (Crawler $node, $i) {
            $sn = $node->filter('.products-name')->attr('href');
            preg_match('/sn=([^&]+)/', $sn, $matches);
            $sn = $matches[1] ?? null;
            $name = $node->filter('.products-name p')->text();
            $image = $node->filter('.products-card-img img')->attr('src');
            $platform = $node->filter('.products-info span')->text();
            $priceText = $node->filter('.products-price')->text();
            $price = filter_var($priceText, FILTER_SANITIZE_NUMBER_INT);

            $point = 0;
            if ($node->filter('.feedback-point')->count() > 0) {
                $pointText = $node->filter('.feedback-point')->text();
                $point = filter_var($pointText, FILTER_SANITIZE_NUMBER_INT);
            }

            return [
                'sn' => $sn,
                'name' => $name,
                'image' => $image,
                'platform' => $platform,
                'point' => (int)$point,
                'price' => (int)$price,
            ];
        });

        foreach ($products as $product) {
            if ($model = $this->productRepository->findProductBySn($product['sn'])) {
                echo sprintf('[已寫入] %s', $model->sn) . PHP_EOL;
            } else {
                $this->productRepository->createOrUpdateFromArray($product);
                echo sprintf('[尚未寫入] %s', $product['sn']) . PHP_EOL;

                $platform = match ($product['platform']) {
                    'MS' => 'Microsoft Store',
                    'PC' => '電腦週邊',
                    'NS' => 'Nintendo Switch',
                    'PS5' => 'PlayStation 5',
                    'PS4' => 'PlayStation 4',
                    'XBSX' => 'Xbox Series X',
                    'XONE' => 'Xbox One',
                    'etc' => '其它',
                    default => $product['platform'],
                };

                $url = (strpos($product['sn'], 'v')) ? 'vItem' : 'atmItem';
                $url = sprintf('https://buy.gamer.com.tw/%s.php?sn=%s', $url, $product['sn']);

                $caption = sprintf(
                    "*%s*\n平台：%s\n預計售價：NT$ %s\n紅利點數：%s",
                    $product['name'],
                    $platform,
                    number_format($product['price']),
                    $product['point'],
                );

                Http::post(sprintf('https://api.telegram.org/bot%s/sendPhoto', config('telegram.bot.token')), [
                    'chat_id' => config('telegram.chat.id'),
                    'photo' => $product['image'],
                    'parse_mode' => 'markdown',
                    'caption' => $caption,
                    'reply_markup' => [
                        'inline_keyboard' => [
                            [
                                [
                                    'text' => '前往預購',
                                    'url' => $url,
                                ],
                            ],
                        ],
                    ],
                ]);
            }
        }

        return Command::SUCCESS;
    }
}
