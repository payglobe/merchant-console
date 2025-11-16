<?php
declare(strict_types=1);

final class ReceiptStore
{
    private \PDO $pdo;
    private string $table;

    public function __construct(\PDO $pdo, string $table)
    {
        $this->pdo   = $pdo;
        $this->table = $table;
    }

    // --------- API PUBBLICA ---------

    /**
     * Salvataggio vendita (SALE) con esito OK
     * $payload  = payload inoltrato ad A-Cube (items, cash/electronic/discount, fiscal_id, order_id, ...)
     * $resp     = risposta A-Cube (ci aspettiamo almeno 'uuid')
     * $payment  = eventuale blocco "payment" passato dall'app (mascherato), o null
     */
    public function saveSaleOk(
        ?string $terminalId,
        ?string $idempotencyKey,
        array $payload,
        array $resp,
        ?array $payment,
        ?string $bodySha = null
    ): void {
        $uuid       = (string)($resp['uuid'] ?? '');
        $opEcho     = 'RECEIPT_SALE';
        $trxType    = 'RCPT_SALE';
        $result     = 'RESULT_OK';
        $nowMs      = (string)round(microtime(true) * 1000);

        $data = $this->buildSaleEnvelope($opEcho, $trxType, $result, $payload, $resp, $payment, $idempotencyKey, $nowMs, $terminalId, $bodySha);

        $this->insertRow($terminalId, $uuid, $data);
    }

    /**
     * Salvataggio vendita (SALE) con esito KO
     * $error = ['http' => int, 'message' => string, 'detail' => ?, 'violations' => ?]
     */
    public function saveSaleKo(
        ?string $terminalId,
        ?string $idempotencyKey,
        array $payload,
        array $error,
        ?array $payment,
        ?string $bodySha = null
    ): void {
        $opEcho  = 'RECEIPT_SALE';
        $trxType = 'RCPT_SALE';
        $result  = 'RESULT_KO';
        $nowMs   = (string)round(microtime(true) * 1000);

        $data = $this->buildSaleEnvelope($opEcho, $trxType, $result, $payload, ['uuid' => ''], $payment, $idempotencyKey, $nowMs, $terminalId, $bodySha);
        $data['acube']['error'] = [
            'http'       => $error['http']    ?? null,
            'title'      => $error['title']   ?? null,
            'detail'     => $error['detail']  ?? ($error['message'] ?? null),
            'violations' => $error['violations'] ?? null,
        ];

        $this->insertRow($terminalId, '', $data);
    }

    /**
     * Salvataggio VOID OK
     */
    public function saveVoidOk(
        ?string $terminalId,
        string $parentUuid,
        ?array $resp = null
    ): void {
        $opEcho   = 'RECEIPT_VOID';
        $trxType  = 'RCPT_VOID';
        $result   = 'RESULT_OK';
        $nowMs    = (string)round(microtime(true) * 1000);
        $uuid     = (string)($resp['uuid'] ?? ''); // spesso il void non crea nuova uuid

        $data = [
            'opEcho'       => $opEcho,
            'trxType'      => $trxType,
            'result'       => $result,
            'uuid'         => $uuid,
            'parent_uuid'  => $parentUuid,
            'gtAmount'     => null,
            'amountCents'  => null,
            'timestamp'    => $nowMs,
            'terminalId'   => $terminalId,
            'goods'        => [],
            'totals'       => ['items_sum' => null, 'cash' => null, 'electronic' => null, 'discount' => null],
            'acube'        => ['response' => $resp ?? new \stdClass()],
        ];

        $this->insertRow($terminalId, $uuid, $data);
    }

    /**
     * Salvataggio RETURN OK
     * $items = righe oggetto del reso (description, quantity, unit_price, vat_rate_code)
     * $resp  = risposta A-Cube del reso (nuova uuid, ecc.)
     */
    public function saveReturnOk(
        ?string $terminalId,
        string $parentUuid,
        array $items,
        ?array $resp = null
    ): void {
        $opEcho  = 'RECEIPT_RETURN';
        $trxType = 'RCPT_RETURN';
        $result  = 'RESULT_OK';
        $nowMs   = (string)round(microtime(true) * 1000);
        $uuid    = (string)($resp['uuid'] ?? '');

        $sum = $this->calcItemsSum($items);
        $goods = $this->mapGoodsFromItems($items);

        $data = [
            'opEcho'       => $opEcho,
            'trxType'      => $trxType,
            'result'       => $result,
            'uuid'         => $uuid,
            'parent_uuid'  => $parentUuid,
            'gtAmount'     => $this->moneyStr($sum),
            'amountCents'  => (int)round($sum * 100),
            'timestamp'    => $nowMs,
            'terminalId'   => $terminalId,
            'goods'        => $goods,
            'totals'       => [
                'items_sum'  => $this->moneyStr($sum),
                'cash'       => $this->moneyStr(0.0),
                'electronic' => $this->moneyStr(0.0),
                'discount'   => $this->moneyStr(0.0),
            ],
            'acube'        => [
                'request'  => ['items' => $items],
                'response' => $resp ?? new \stdClass(),
            ],
        ];

        $this->insertRow($terminalId, $uuid, $data);
    }

    // --------- BUILDERS ---------

    private function buildSaleEnvelope(
        string $opEcho,
        string $trxType,
        string $result,
        array $payload,
        array $resp,
        ?array $payment,
        ?string $idempotencyKey,
        string $tsMs,
        ?string $terminalId,
        ?string $bodySha
    ): array {
        $items  = (array)($payload['items'] ?? []);
        $sum    = $this->calcItemsSum($items);
        $cash   = (float)($payload['cash_payment_amount']       ?? 0);
        $elec   = (float)($payload['electronic_payment_amount']  ?? 0);
        $disc   = (float)($payload['discount'] ?? 0);
        $gt     = $cash + $elec;

        $goods  = $this->mapGoodsFromItems($items);

        return [
            'opEcho'      => $opEcho,
            'trxType'     => $trxType,
            'result'      => $result,
            'uuid'        => (string)($resp['uuid'] ?? ''),
            'gtAmount'    => $this->moneyStr($gt),
            'amountCents' => (int)round($gt * 100),
            'timestamp'   => $tsMs,
            'terminalId'  => $terminalId,

            // blocco pagamento opzionale
            'payment'     => $payment ?: new \stdClass(),

            'goods'  => $goods,
            'totals' => [
                'items_sum'  => $this->moneyStr($sum),
                'cash'       => $this->moneyStr($cash),
                'electronic' => $this->moneyStr($elec),
                'discount'   => $this->moneyStr($disc),
            ],

            'acube' => [
                'fiscal_id'        => $payload['fiscal_id'] ?? null,
                'order_id'         => $payload['order_id']  ?? null,
                'request'          => $payload,
                'response'         => $resp,
                'idempotency_key'  => $idempotencyKey,
            ],

            'meta' => [
                'body_sha256'     => $bodySha,
                'wrapper_version' => 'acube-wrapper/1.0',
            ],
        ];
    }

    private function mapGoodsFromItems(array $items): array
    {
        $out = [];
        foreach ($items as $r) {
            $out[] = [
                'desc' => (string)($r['description'] ?? ''),
                'qty'  => isset($r['quantity']) ? (float)$r['quantity'] : 0.0,
                'unit' => $this->moneyStr((float)($r['unit_price'] ?? 0)),
                'vat'  => (string)($r['vat_rate_code'] ?? ''),
            ];
        }
        return $out;
    }

    private function calcItemsSum(array $items): float
    {
        $sum = 0.0;
        foreach ($items as $r) {
            $q  = (float)($r['quantity']   ?? 0);
            $up = (float)($r['unit_price'] ?? 0);
            $d  = (float)($r['discount']   ?? 0);
            $line = round($q * $up - $d, 2);
            $sum  = round($sum + $line, 2);
        }
        return $sum;
    }

    private function moneyStr(float $v): string
    {
        return number_format($v, 2, '.', '');
    }

    // --------- DB I/O ---------

    private function insertRow(?string $terminalId, string $uuid, array $data): void
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE|JSON_PRESERVE_ZERO_FRACTION);

        // ON DUPLICATE KEY UPDATE aggiorna data/created_at se esiste un UNIQUE su uuid
        $sql = "INSERT INTO {$this->table} (terminalID, uuid, data)
                VALUES (:terminalID, :uuid, :data)
                ON DUPLICATE KEY UPDATE data = VALUES(data)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':terminalID' => $terminalId,
            ':uuid'       => ($uuid !== '' ? $uuid : null),
            ':data'       => $json,
        ]);
    }
}

