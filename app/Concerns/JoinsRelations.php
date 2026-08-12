<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

trait JoinsRelations
{
    /**
     * @param  Builder<Transaction>  $query
     * @return Builder<Transaction>
     */
    public function scopeLeftJoinRelation(Builder $query, string $relation): Builder
    {
        return $this->joinRelations($query, $relation, 'left');
    }

    /**
     * @param  Builder<Transaction>  $query
     * @param  array<int|string, string>  $relations
     * @return Builder<Transaction>
     */
    public function scopeLeftJoinRelations(Builder $query, array $relations): Builder
    {
        return $this->joinRelations($query, $relations, 'left');
    }

    /**
     * @param  Builder<Transaction>  $query
     * @return Builder<Transaction>
     */
    public function scopeJoinRelation(Builder $query, string $relation): Builder
    {
        return $this->joinRelations($query, $relation, 'inner');
    }

    /**
     * @param  Builder<Transaction>  $query
     * @param  array<int|string, string>  $relations
     * @return Builder<Transaction>
     */
    public function scopeJoinRelations(Builder $query, array $relations): Builder
    {
        return $this->joinRelations($query, $relations, 'inner');
    }

    /**
     * Automatically joins related tables (including nested relations) into the main query.
     * Performance priority: solves N+1 by merging multiple relations into a single SQL query.
     *
     * @param  Builder<Transaction>  $query
     * @param  array<int|string, string>|string  $relations  Relation paths (e.g. 'user.profile' or ['posts' => 'p'])
     * @param  string  $joinType  'left' or 'inner'
     * @return Builder<Transaction>
     */
    private function joinRelations(Builder $query, array|string $relations, string $joinType = 'left'): Builder
    {
        // Normalize single string input into an array
        if (is_string($relations)) {
            $relations = [$relations];
        }

        // Resolve join method once
        $joinMethod = strtolower($joinType) === 'inner' ? 'join' : 'leftJoin';

        // Base table reference
        $baseTable = $query->getModel()->getTable();

        // Track already joined aliases to avoid duplicate joins
        $joinedAliases = [];

        foreach ($relations as $relationPath => $customAlias) {
            // Normalize formats:
            // - ['order.customer'] (numeric key)
            // - ['order.customer' => 'cust'] (custom alias for final step)
            if (is_int($relationPath)) {
                $relationPath = $customAlias;
                $customAlias = null;
            }

            $parts = explode('.', $relationPath);

            // Walk the chain starting from the base model/table
            $currentModel = $query->getModel();
            $currentRef = $baseTable; // base reference is the table name

            $aliasChain = [];

            foreach ($parts as $i => $part) {
                $aliasChain[] = $part;

                // Alias strategy:
                // - use custom alias only on the last step (if provided)
                // - otherwise build a predictable alias from the chain
                $aliasStep = ($i === count($parts) - 1 && $customAlias)
                    ? $customAlias
                    : implode('_', $aliasChain);

                // If this alias already joined, advance chain without re-joining
                if (in_array($aliasStep, $joinedAliases, true)) {
                    $relation = $currentModel->$part();
                    $currentModel = $relation->getRelated();
                    $currentRef = $aliasStep; // from now on, current reference is the alias

                    continue;
                }

                // Resolve relationship and related model/table
                $relation = $currentModel->$part();
                $related = $relation->getRelated();
                $relatedTable = $related->getTable();

                // Build "table as alias"
                $aliasSql = "{$relatedTable} as {$aliasStep}";

                // getQualifiedForeignKeyName() returns "table.column" -> we need only "column"
                $foreignKey = Str::after($relation->getQualifiedForeignKeyName(), '.');

                if ($relation instanceof BelongsTo) {
                    // BelongsTo:
                    // currentRef.foreignKey = aliasStep.ownerKey
                    $ownerKey = Str::after($relation->getQualifiedOwnerKeyName(), '.');

                    $query->{$joinMethod}(
                        $aliasSql,
                        "{$currentRef}.{$foreignKey}",
                        '=',
                        "{$aliasStep}.{$ownerKey}"
                    );
                } elseif ($relation instanceof HasOne || $relation instanceof HasMany) {
                    // HasOne/HasMany:
                    // aliasStep.foreignKey = currentRef.localKey
                    $localKey = Str::after($relation->getQualifiedParentKeyName(), '.');

                    $query->{$joinMethod}(
                        $aliasSql,
                        "{$aliasStep}.{$foreignKey}",
                        '=',
                        "{$currentRef}.{$localKey}"
                    );
                } else {
                    // Not handled: BelongsToMany, morph relations, etc.
                    // You can extend this with additional relation handlers if needed.
                    $currentModel = $related;
                    $currentRef = $aliasStep;

                    continue;
                }

                $joinedAliases[] = $aliasStep;

                // Advance for nested joins
                $currentModel = $related;
                $currentRef = $aliasStep;
            }
        }

        return $query;
    }
}
