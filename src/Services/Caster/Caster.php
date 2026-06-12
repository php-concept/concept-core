<?php declare(strict_types=1);

namespace Concept\Core\Services\Caster;

use Concept\Core\Services\Caster\Contracts\CasterInterface;
use Concept\Core\Services\Caster\Exceptions\CastingException;
use Concept\Core\Services\Config\Contracts\ConfigInterface;
use Concept\Core\Foundation\ConfigKey;
use Concept\Core\Foundation\PathManager;
use Concept\Core\Foundation\PathName;
use CuyZ\Valinor\Cache\FileSystemCache;
use CuyZ\Valinor\Cache\FileWatchingCache;
use CuyZ\Valinor\Mapper\MappingError;
use CuyZ\Valinor\Mapper\TreeMapper;
use CuyZ\Valinor\MapperBuilder;

/**
 * Implementation of CasterInterface using the Valinor library.
 */
class Caster implements CasterInterface
{
    private const string VALINOR_CACHE_DIR = 'valinor';

    private TreeMapper $mapper;

    public function __construct(
        private readonly PathManager $pathManager,
        private readonly ConfigInterface $config
    ) {
        $cache = new FileSystemCache($this->pathManager->get(PathName::CACHE, self::VALINOR_CACHE_DIR));
        if ($this->config->getBool(ConfigKey::APP_DEBUG)) {
            $cache = new FileWatchingCache($cache);
        }

        $this->mapper = (new MapperBuilder())
            ->withCache($cache)
            ->allowScalarValueCasting()
            ->allowSuperfluousKeys()
            ->mapper();
    }

    public function cast(mixed $value, string $type): mixed
    {
        try {
            return $this->mapper->map($type, $value);
        } catch (MappingError $e) {
            throw new CastingException($type, $e);
        }
    }
}