<?php

namespace Baaz\Workers;

use Baaz\Models\Product;
use Baaz\Workers\Traits\SolrWorkerTrait;
use Predis\Client as Predis;
use Predis\Collection\Iterator\Keyspace;
use ⌬\Services\EnvironmentService;

class SolrIngester extends GenericWorker
{
    use SolrWorkerTrait;

    public const CACHE_PATH = __DIR__.'/../../cache/';
    /** @var Predis */
    protected $predis;

    public function __construct(
        Predis $predis,
        EnvironmentService $environmentService
    ) {
        $this->predis = $predis;
        parent::__construct($environmentService);
    }

    public function run()
    {
        $solr = $this->getSolr();
        while(true) {
            if(!$this->doSolrPing()){
                echo "Solr Ping Failed\n";
                return;
            }

            $match = 'queue:solr-loader:*';
            $pipeline = $this->predis->pipeline();

            foreach (new Keyspace($this->predis, $match) as $key) {
                $productUUID = $this->predis->get($key);
                /** @var Product $product */
                $product = (new Product($this->predis))->load($productUUID);
                $update = $solr->createUpdate();

                $document = $product->createSolrDocument($update);
                $update->addDocument($document);
                $update->addCommit();
                $result = $solr->update($update);
                $this->predis->del($key);

                printf(
                    'Wrote Product %s to Solr, %d left in queue'.PHP_EOL,
                    $product->getSlug(),
                    count($this->predis->keys($match))
                );
            }
            $pipeline->flushPipeline();
            echo "No work to be done, sleeping...\n";
            while (count($this->predis->keys($match)) == 0) {
                sleep(5);
            }
        }
    }
}