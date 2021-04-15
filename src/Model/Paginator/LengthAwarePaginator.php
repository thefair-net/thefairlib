<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace TheFairLib\Model\Paginator;

use ArrayAccess;
use Countable;
use Hyperf\Contract\LengthAwarePaginatorInterface;
use Hyperf\Paginator\AbstractPaginator;
use Hyperf\Paginator\UrlWindow;
use Hyperf\Utils\Collection;
use Hyperf\Utils\Contracts\Arrayable;
use Hyperf\Utils\Contracts\Jsonable;
use IteratorAggregate;
use JsonSerializable;
use TheFairLib\Exception\ServiceException;

class LengthAwarePaginator extends AbstractPaginator implements Arrayable, ArrayAccess, Countable, IteratorAggregate, JsonSerializable, Jsonable, LengthAwarePaginatorInterface
{
    /**
     * The total number of items before slicing.
     *
     * @var int
     */
    protected $total;

    /**
     * The last available page.
     *
     * @var int
     */
    protected $lastPage;

    /**
     * Create a new paginator instance.
     *
     * @param mixed $items
     * @param int $total
     * @param int $perPage
     * @param null|int $currentPage
     * @param array $options (path, query, fragment, pageName)
     */
    public function __construct($items, $total, $perPage, $currentPage = 1, array $options = [])
    {
        foreach ($options as $key => $value) {
            $this->{$key} = $value;
        }
        if ($perPage > 50) {
            throw new ServiceException('per page max 50', [
                'per_age' => $perPage,
            ]);
        }
        $perPage = min(50, $perPage);
        $this->total = $total;
        $this->perPage = $perPage;
        $this->lastPage = max((int)ceil($total / $perPage), 1);
        $this->path = $this->path !== '/' ? rtrim($this->path, '/') : $this->path;
        $this->currentPage = $this->setCurrentPage($currentPage, $this->pageName);
        $this->items = $items instanceof Collection ? $items : Collection::make($items);
    }

    /**
     * Render the paginator using the given view.
     * @param string|null $view
     * @param array $data
     * @return string
     */
    public function links(?string $view = null, array $data = [])
    {
        return $this->render($view, $data);
    }

    /**
     * Render the paginator using the given view.
     * @param string|null $view
     * @param array $data
     * @return string
     */
    public function render(?string $view = null, array $data = []): string
    {
        if ($view) {
            throw new \RuntimeException('WIP.');
        }
        return json_encode(array_merge($data, $this->items()), 0);
    }

    /**
     * Get the total number of items being paginated.
     */
    public function total(): int
    {
        return $this->total;
    }

    /**
     * Determine if there are more items in the data source.
     */
    public function hasMorePages(): bool
    {
        return $this->currentPage() < $this->lastPage();
    }

    /**
     * Get the URL for the next page.
     */
    public function nextPageUrl(): ?string
    {
        if ($this->lastPage() > $this->currentPage()) {
            return $this->url($this->currentPage() + 1);
        }

        return null;
    }

    /**
     * Get the last page.
     */
    public function lastPage(): int
    {
        return $this->lastPage;
    }

    /**
     * Get the instance as an array.
     */
    public function toArray(): array
    {
        $result = [
            'item_list' => $this->items->toArray(),
            'page' => $this->currentPage(),
            'item_per_page' => $this->perPage(),
            'item_count' => $this->total(),
            'page_count' => $this->lastPage(),
        ];
        if (($itemLastId = $this->getItemLastId()) >= 0) {
            $result['last_item_id'] = $itemLastId;
        }
        return $result;
    }

    /**
     * Get the last_item_id page.
     */
    public function getItemLastId(): int
    {
        $itemLastId = 0;
        if ($this->lastPage() > $this->currentPage()) {
            $itemLastId = $this->currentPage() + 1;
        }
        if ($this->currentPage() >= $this->lastPage()) {
            $itemLastId = -1;//最后一页
        }
        return $itemLastId;
    }

    /**
     * Convert the object into something JSON serializable.
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Convert the object to its JSON representation.
     * @param int $options
     * @return string
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Get the current page for the request.
     * @param int|null $currentPage
     * @param string $pageName
     * @return int
     */
    protected function setCurrentPage(?int $currentPage, string $pageName): int
    {
        $currentPage = $currentPage ?: static::resolveCurrentPage($pageName);

        return $this->isValidPageNumber($currentPage) ? (int)$currentPage : 1;
    }

    /**
     * Get the array of elements to pass to the view.
     */
    protected function elements(): array
    {
        $window = UrlWindow::make($this);

        return array_filter([
            $window['first'],
            is_array($window['slider']) ? '...' : null,
            $window['slider'],
            is_array($window['last']) ? '...' : null,
            $window['last'],
        ]);
    }
}
