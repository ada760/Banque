<?php

namespace App\Repositories\OmPay;

use App\Models\OmPay\Transaction;
use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class TransactionRepository implements BaseRepositoryInterface
{
    protected Transaction $model;

    public function __construct(Transaction $model)
    {
        $this->model = $model;
    }

    public function all(array $filters = [], int $page = 1, int $limit = 10): mixed
    {
        $query = $this->model->newQuery();

        // Appliquer les filtres
        if (!empty($filters)) {
            foreach ($filters as $key => $value) {
                if ($value !== null) {
                    $query->where($key, $value);
                }
            }
        }

        return $query->paginate($limit, ['*'], 'page', $page);
    }

    public function find($id): mixed
    {
        return $this->model->find($id);
    }

    public function create(array $data): mixed
    {
        return $this->model->create($data);
    }

    public function update($id, array $data): mixed
    {
        $transaction = $this->find($id);
        if ($transaction) {
            $transaction->update($data);
            return $transaction;
        }
        return null;
    }

    public function delete($id): bool
    {
        $transaction = $this->find($id);
        return $transaction ? $transaction->delete() : false;
    }

    // Méthodes spécifiques OM Pay
    public function getByUser($userId, $limit = 10)
    {
        return $this->model->byUser($userId)->recent()->limit($limit)->get();
    }

    public function getSuccessfulTransactions()
    {
        return $this->model->successful()->get();
    }

    public function getTransactionsByType($type)
    {
        return $this->model->where('type', $type)->get();
    }

    public function getTotalAmountByUser($userId)
    {
        return $this->model->byUser($userId)->successful()->sum('amount');
    }
}