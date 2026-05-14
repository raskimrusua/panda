import { useTranslation } from 'react-i18next';
import { Card, CardBody } from '@/components/ui/Card';
import { HelpTooltip } from '@/components/ui/HelpTooltip';
import { formatKes } from '@/lib/utils';

export interface ProfitSummaryCardProps {
  totalCost: number;
  totalRevenue: number;
  className?: string;
}

/*
 * Three-cell summary card: Total cost · Total revenue · Profit (or Loss).
 * Sits at the top of Season detail above the tab strip so the lender-facing
 * number is the first thing a farmer sees on the season's page.
 *
 * Each cell carries a HelpTooltip with the formula in plain language —
 * directly addresses Joshua's "we also need a link for the farmer to
 * understand the formula behind the calculations" ask.
 *
 * Loss is rendered in danger-600; profit in highland-green. Zero-state
 * (no costs + no revenue yet) renders all three as "—" so the card still
 * appears consistently for an empty season.
 */
export function ProfitSummaryCard({ totalCost, totalRevenue, className }: ProfitSummaryCardProps) {
  const { t } = useTranslation();
  const profit = totalRevenue - totalCost;
  const hasAnyData = totalCost > 0 || totalRevenue > 0;

  const fmt = (n: number) => (hasAnyData ? formatKes(n) : '—');

  return (
    <Card className={className}>
      <CardBody className="grid grid-cols-1 gap-3 md:grid-cols-3">
        <Cell
          label={t('seasons.summary_cost')}
          tooltipTitle={t('help.summary_cost_title')}
          tooltipBody={t('help.summary_cost_body')}
          value={fmt(totalCost)}
          tone="neutral"
        />
        <Cell
          label={t('seasons.summary_revenue')}
          tooltipTitle={t('help.summary_revenue_title')}
          tooltipBody={t('help.summary_revenue_body')}
          value={fmt(totalRevenue)}
          tone="neutral"
        />
        <Cell
          label={profit >= 0 ? t('seasons.summary_profit') : t('seasons.summary_loss')}
          tooltipTitle={t('help.summary_profit_title')}
          tooltipBody={t('help.summary_profit_body')}
          value={fmt(profit)}
          tone={hasAnyData ? (profit >= 0 ? 'positive' : 'negative') : 'neutral'}
        />
      </CardBody>
    </Card>
  );
}

interface CellProps {
  label: string;
  tooltipTitle: string;
  tooltipBody: string;
  value: string;
  tone: 'neutral' | 'positive' | 'negative';
}

function Cell({ label, tooltipTitle, tooltipBody, value, tone }: CellProps) {
  const valueClass =
    tone === 'positive'
      ? 'text-2xl font-semibold text-brand-700'
      : tone === 'negative'
        ? 'text-2xl font-semibold text-danger-600'
        : 'text-2xl font-semibold text-gray-900';
  return (
    <div>
      <div className="flex items-center gap-1 text-sm text-gray-500">
        {label}
        <HelpTooltip title={tooltipTitle} body={tooltipBody} />
      </div>
      <div className={valueClass}>{value}</div>
    </div>
  );
}
