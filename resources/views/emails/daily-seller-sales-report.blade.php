<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório Diário de Vendas</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #4CAF50;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #4CAF50;
            margin: 0;
            font-size: 24px;
        }
        .greeting {
            font-size: 18px;
            margin-bottom: 20px;
        }
        .report-info {
            background-color: #f9f9f9;
            border-left: 4px solid #4CAF50;
            padding: 15px;
            margin: 20px 0;
        }
        .metric {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .metric:last-child {
            border-bottom: none;
        }
        .metric-label {
            font-weight: bold;
            color: #555;
        }
        .metric-value {
            color: #4CAF50;
            font-weight: bold;
            font-size: 18px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            color: #777;
            font-size: 14px;
        }
        .highlight {
            background-color: #fff9e6;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Relatório Diário de Vendas</h1>
            <p style="margin: 5px 0; color: #777;">{{ $reportDate }}</p>
        </div>

        <div class="greeting">
            Olá, <strong>{{ $sellerName }}</strong>!
        </div>

        <p>Aqui está o resumo do seu desempenho de vendas de ontem:</p>

        <div class="report-info">
            <div class="metric">
                <span class="metric-label">Total de Vendas:</span>
                <span class="metric-value">{{ $totalSales }}</span>
            </div>
            <div class="metric">
                <span class="metric-label">Valor Total:</span>
                <span class="metric-value">R$ {{ number_format($totalAmount, 2, ',', '.') }}</span>
            </div>
            <div class="metric">
                <span class="metric-label">Comissão Total:</span>
                <span class="metric-value">R$ {{ number_format($totalCommission, 2, ',', '.') }}</span>
            </div>
        </div>

        <div class="footer">
            <p>Este é um e-mail automático. Por favor, não responda.</p>
            <p>&copy; {{ date('Y') }} Sales Control. Todos os direitos reservados.</p>
        </div>
    </div>
</body>
</html>

