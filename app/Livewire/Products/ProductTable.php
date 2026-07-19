<?php

namespace App\Livewire\Products;

use App\Models\Product;
use App\Models\Category;
use App\Models\Unit;
use App\Services\ProductService;
use App\Exceptions\ProductException;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;

class ProductTable extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'category')]
    public string $categoryId = '';

    #[Url(as: 'unit')]
    public string $unitId = '';

    #[Url(as: 'status')]
    public string $status = '';

    public int $perPage = 12;

    protected $listeners = [
        'pg:eventRefresh-product-table' => 'refreshTable',
        'delete' => 'delete'
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCategoryId(): void
    {
        $this->resetPage();
    }

    public function updatingUnitId(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    #[On('pg:eventRefresh-product-table')]
    public function refreshTable(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = Product::query()
            ->with(['category', 'unit'])
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('name', 'ilike', '%' . $this->search . '%')
                       ->orWhere('sku', 'ilike', '%' . $this->search . '%');
                });
            })
            ->when($this->categoryId, function ($q) {
                $q->where('category_id', $this->categoryId);
            })
            ->when($this->unitId, function ($q) {
                $q->where('unit_id', $this->unitId);
            })
            ->when($this->status !== '', function ($q) {
                $isActive = $this->status === 'active';
                $q->where('is_active', $isActive);
            })
            ->orderBy('created_at', 'desc');

        return view('livewire.products.product-table', [
            'products' => $query->paginate($this->perPage),
            'categories' => Category::orderBy('name')->get(),
            'units' => Unit::orderBy('name')->get(),
        ]);
    }

    public function delete($rowId, ProductService $service): void
    {
        $product = Product::find($rowId);

        if ($product) {
            try {
                $service->deleteProduct($product);
                $this->dispatch('toast', message: 'Product deleted successfully.', type: 'success');
            } catch (\Exception $e) {
                $message = $e instanceof ProductException
                    ? $e->getMessage()
                    : 'Failed to delete product: ' . $e->getMessage();

                $this->dispatch('toast', message: $message, type: 'error');
            }
        }
    }
}
