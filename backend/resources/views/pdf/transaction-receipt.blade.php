<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Extrato de Transação</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
      font-family: 'DejaVu Sans', Arial, sans-serif;
      font-size: 13px;
      color: #18181b;
      background: #ffffff;
    }

    .page {
      padding: 40px 48px;
      min-height: 100%;
    }

    /* ── HEADER ── */
    .header {
      border-bottom: 2px solid #09090b;
      padding-bottom: 20px;
      margin-bottom: 28px;
    }

    .header-top {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
    }

    .brand {
      font-size: 20px;
      font-weight: 700;
      color: #09090b;
      letter-spacing: -0.5px;
    }

    .brand-sub {
      font-size: 11px;
      color: #71717a;
      margin-top: 2px;
    }

    .receipt-meta {
      text-align: right;
    }

    .receipt-meta .label {
      font-size: 10px;
      color: #71717a;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .receipt-meta .value {
      font-size: 11px;
      color: #3f3f46;
      margin-top: 2px;
      word-break: break-all;
    }

    /* ── HERO ── */
    .hero {
      background: #f4f4f5;
      border-radius: 12px;
      padding: 24px 28px;
      margin-bottom: 28px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .hero-left .type-badge {
      display: inline-block;
      padding: 4px 12px;
      border-radius: 9999px;
      font-size: 11px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 10px;
    }

    .badge-deposit    { background: #dcfce7; color: #15803d; }
    .badge-withdrawal { background: #fee2e2; color: #b91c1c; }
    .badge-transfer   { background: #dbeafe; color: #1d4ed8; }
    .badge-reversal   { background: #fef9c3; color: #92400e; }

    .hero-left .amount {
      font-size: 32px;
      font-weight: 700;
      color: #09090b;
      letter-spacing: -1px;
    }

    .hero-left .currency {
      font-size: 14px;
      color: #71717a;
      margin-left: 4px;
    }

    .status-pill {
      display: inline-block;
      padding: 6px 16px;
      border-radius: 9999px;
      font-size: 11px;
      font-weight: 600;
    }

    .status-completed { background: #dcfce7; color: #15803d; }
    .status-pending   { background: #fef9c3; color: #92400e; }
    .status-failed    { background: #fee2e2; color: #b91c1c; }
    .status-reversed  { background: #ede9fe; color: #6d28d9; }

    /* ── SECTIONS ── */
    .section {
      margin-bottom: 24px;
    }

    .section-title {
      font-size: 10px;
      font-weight: 700;
      color: #71717a;
      text-transform: uppercase;
      letter-spacing: 0.8px;
      margin-bottom: 12px;
      padding-bottom: 6px;
      border-bottom: 1px solid #e4e4e7;
    }

    .field-row {
      display: flex;
      justify-content: space-between;
      padding: 8px 0;
      border-bottom: 1px dashed #f4f4f5;
    }

    .field-row:last-child {
      border-bottom: none;
    }

    .field-label {
      font-size: 12px;
      color: #71717a;
      flex: 0 0 45%;
    }

    .field-value {
      font-size: 12px;
      color: #18181b;
      font-weight: 500;
      text-align: right;
      flex: 0 0 53%;
      word-break: break-all;
    }

    .field-value.mono {
      font-family: 'DejaVu Sans Mono', monospace;
      font-size: 10px;
      color: #52525b;
    }

    /* ── HIGHLIGHT BOX ── */
    .highlight-box {
      background: #f4f4f5;
      border-left: 3px solid #3f3f46;
      border-radius: 4px;
      padding: 12px 16px;
      margin-top: 8px;
    }

    .highlight-box .hl-label {
      font-size: 10px;
      color: #71717a;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 4px;
    }

    .highlight-box .hl-name {
      font-size: 14px;
      font-weight: 600;
      color: #09090b;
    }

    .highlight-box .hl-id {
      font-size: 10px;
      color: #71717a;
      font-family: 'DejaVu Sans Mono', monospace;
      margin-top: 2px;
      word-break: break-all;
    }

    .two-col {
      display: flex;
      gap: 16px;
    }

    .two-col > div {
      flex: 1;
    }

    /* ── WARNING BOX ── */
    .warning-box {
      background: #fffbeb;
      border: 1px solid #fde68a;
      border-radius: 8px;
      padding: 12px 16px;
      margin-top: 8px;
    }

    .warning-box p {
      font-size: 11px;
      color: #92400e;
    }

    /* ── DESCRIPTION ── */
    .description-box {
      background: #f4f4f5;
      border-radius: 8px;
      padding: 12px 16px;
    }

    .description-box p {
      font-size: 12px;
      color: #3f3f46;
      line-height: 1.5;
    }

    /* ── FOOTER ── */
    .footer {
      margin-top: 36px;
      padding-top: 16px;
      border-top: 1px solid #e4e4e7;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .footer p {
      font-size: 10px;
      color: #a1a1aa;
    }

    .footer .generated {
      text-align: right;
    }
  </style>
</head>
<body>
<div class="page">

  {{-- HEADER --}}
  <div class="header">
    <div class="header-top">
      <div>
        <div class="brand">Carteira Financeira</div>
        <div class="brand-sub">Comprovante de Transação</div>
      </div>
      <div class="receipt-meta">
        <div class="label">Nº do Comprovante</div>
        <div class="value">{{ $transaction->uuid }}</div>
      </div>
    </div>
  </div>

  {{-- HERO: tipo + valor + status --}}
  <div class="hero">
    <div class="hero-left">
      @php $type = $transaction->type; @endphp
      <span class="type-badge badge-{{ $type }}">
        {{ $typeLabels[$type] ?? $type }}
      </span>
      <div>
        <span class="amount">
          R$ {{ number_format((float) $transaction->amount, 2, ',', '.') }}
        </span>
        <span class="currency">{{ $transaction->currency }}</span>
      </div>
    </div>
    <div>
      <span class="status-pill status-{{ $transaction->status }}">
        {{ $statusLabels[$transaction->status] ?? $transaction->status }}
      </span>
    </div>
  </div>

  {{-- DADOS GERAIS --}}
  <div class="section">
    <div class="section-title">Dados da Transação</div>

    <div class="field-row">
      <span class="field-label">Data e Hora</span>
      <span class="field-value">
        {{ \Carbon\Carbon::parse($transaction->created_at)->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i:s') }}
      </span>
    </div>

    <div class="field-row">
      <span class="field-label">Tipo</span>
      <span class="field-value">{{ $typeLabels[$type] ?? $type }}</span>
    </div>

    <div class="field-row">
      <span class="field-label">Status</span>
      <span class="field-value">{{ $statusLabels[$transaction->status] ?? $transaction->status }}</span>
    </div>

    <div class="field-row">
      <span class="field-label">Moeda</span>
      <span class="field-value">{{ $transaction->currency }}</span>
    </div>

    @if ($transaction->processed_at)
    <div class="field-row">
      <span class="field-label">Processado em</span>
      <span class="field-value">
        {{ \Carbon\Carbon::parse($transaction->processed_at)->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i:s') }}
      </span>
    </div>
    @endif
  </div>

  {{-- ── DEPÓSITO ── --}}
  @if ($type === 'deposit')
  <div class="section">
    <div class="section-title">Detalhes do Depósito</div>
    <div class="highlight-box">
      <div class="hl-label">Conta creditada</div>
      <div class="hl-name">{{ $transaction->toWallet?->user?->name ?? '—' }}</div>
      <div class="hl-id">ID da carteira: {{ $transaction->toWallet?->uuid ?? '—' }}</div>
    </div>
  </div>
  @endif

  {{-- ── SAQUE ── --}}
  @if ($type === 'withdrawal')
  <div class="section">
    <div class="section-title">Detalhes do Saque</div>
    <div class="highlight-box">
      <div class="hl-label">Conta debitada</div>
      <div class="hl-name">{{ $transaction->fromWallet?->user?->name ?? '—' }}</div>
      <div class="hl-id">ID da carteira: {{ $transaction->fromWallet?->uuid ?? '—' }}</div>
    </div>
  </div>
  @endif

  {{-- ── TRANSFERÊNCIA ── --}}
  @if ($type === 'transfer')
  <div class="section">
    <div class="section-title">Detalhes da Transferência</div>
    <div class="two-col">
      <div class="highlight-box">
        <div class="hl-label">Remetente</div>
        <div class="hl-name">{{ $transaction->fromWallet?->user?->name ?? '—' }}</div>
        <div class="hl-id">ID: {{ $transaction->fromWallet?->uuid ?? '—' }}</div>
      </div>
      <div class="highlight-box">
        <div class="hl-label">Destinatário</div>
        <div class="hl-name">{{ $transaction->toWallet?->user?->name ?? '—' }}</div>
        <div class="hl-id">ID: {{ $transaction->toWallet?->uuid ?? '—' }}</div>
      </div>
    </div>

    @if ($transaction->status === 'reversed' && $reversalInfo)
    <div class="warning-box" style="margin-top:14px;">
      <p><strong>Esta transferência foi revertida.</strong></p>
      @if ($reversalInfo->requestedBy)
        <p style="margin-top:4px;">Solicitado por: {{ $reversalInfo->requestedBy->name }}</p>
      @endif
      @if ($reversalInfo->approved_at)
        <p style="margin-top:2px;">
          Revertida em:
          {{ \Carbon\Carbon::parse($reversalInfo->approved_at)->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i:s') }}
        </p>
      @endif
    </div>
    @endif
  </div>
  @endif

  {{-- ── REVERSÃO ── --}}
  @if ($type === 'reversal')
  <div class="section">
    <div class="section-title">Detalhes da Reversão</div>

    @if ($reversalRecord)
      <div class="field-row">
        <span class="field-label">Transação original revertida</span>
        <span class="field-value mono">{{ $reversalRecord->originalTransaction?->uuid ?? '—' }}</span>
      </div>

      <div class="field-row">
        <span class="field-label">Data da transação original</span>
        <span class="field-value">
          {{ $reversalRecord->originalTransaction?->created_at
              ? \Carbon\Carbon::parse($reversalRecord->originalTransaction->created_at)->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i:s')
              : '—' }}
        </span>
      </div>

      <div class="field-row">
        <span class="field-label">Valor revertido</span>
        <span class="field-value">
          R$ {{ number_format((float) $transaction->amount, 2, ',', '.') }}
        </span>
      </div>

      <div class="field-row">
        <span class="field-label">Motivo</span>
        <span class="field-value">
          @php
            $reasonLabels = [
              'user_request'     => 'Solicitação do usuário',
              'system_error'     => 'Erro do sistema',
              'fraud_detection'  => 'Suspeita de fraude',
              'compliance'       => 'Conformidade',
            ];
          @endphp
          {{ $reasonLabels[$reversalRecord->reason] ?? $reversalRecord->reason }}
        </span>
      </div>

      @if ($reversalRecord->requestedBy)
      <div class="field-row">
        <span class="field-label">Solicitado por</span>
        <span class="field-value">{{ $reversalRecord->requestedBy->name }}</span>
      </div>
      @endif

      <div style="margin-top:14px;">
        <div class="two-col">
          <div class="highlight-box">
            <div class="hl-label">Valor debitado de (destinatário original)</div>
            <div class="hl-name">{{ $reversalRecord->originalTransaction?->toWallet?->user?->name ?? '—' }}</div>
            <div class="hl-id">ID: {{ $reversalRecord->originalTransaction?->toWallet?->uuid ?? '—' }}</div>
          </div>
          <div class="highlight-box">
            <div class="hl-label">Valor devolvido para (remetente original)</div>
            <div class="hl-name">{{ $reversalRecord->originalTransaction?->fromWallet?->user?->name ?? '—' }}</div>
            <div class="hl-id">ID: {{ $reversalRecord->originalTransaction?->fromWallet?->uuid ?? '—' }}</div>
          </div>
        </div>
      </div>
    @else
      <p style="font-size:12px; color:#71717a;">Dados de reversão não disponíveis.</p>
    @endif
  </div>
  @endif

  {{-- DESCRIÇÃO --}}
  @if ($transaction->description)
  <div class="section">
    <div class="section-title">Descrição</div>
    <div class="description-box">
      <p>{{ $transaction->description }}</p>
    </div>
  </div>
  @endif

  {{-- FOOTER --}}
  <div class="footer">
    <p>Carteira Financeira &bull; Documento gerado automaticamente</p>
    <div class="generated">
      <p>Gerado em {{ $generatedAt }}</p>
      <p style="margin-top:2px; font-family: 'DejaVu Sans Mono', monospace; font-size:9px;">{{ $transaction->uuid }}</p>
    </div>
  </div>

</div>
</body>
</html>
