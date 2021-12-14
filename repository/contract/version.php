<?php
namespace repository\contract;

use hydra\Result;
use model\Version;

interface IVersionRepository
{
    public function begin():Result;
    public function exists(int $id):Version;
    public function increment(Version $version):Result;
}
?>