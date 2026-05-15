import { useTranslation } from 'react-i18next';

export interface ActivityProgressBarProps {
  done: number;
  total: number;
  className?: string;
}

/*
 * Compact horizontal progress bar shown at the top of the Timeline tab.
 * Renders "12 of 35 activities done (34%)" with a filled bar underneath.
 *
 * Pure presentational — caller computes the counts from the timeline
 * query so the bar updates when an activity gets marked done without
 * a refetch.
 */
export function ActivityProgressBar({ done, total, className }: ActivityProgressBarProps) {
  const { t } = useTranslation();
  const safeTotal = Math.max(total, 1);
  const pct = total === 0 ? 0 : Math.round((done / safeTotal) * 100);
  const isComplete = total > 0 && done >= total;

  return (
    <div className={className}>
      <div className="flex items-baseline justify-between text-sm mb-1">
        <span className="text-gray-700">
          {t('seasons.progress_label', { done, total })}
        </span>
        <span className={isComplete ? 'font-semibold text-brand-700' : 'text-gray-500'}>
          {pct}%
        </span>
      </div>
      <div
        className="h-2 w-full overflow-hidden rounded-full bg-gray-100"
        role="progressbar"
        aria-valuenow={pct}
        aria-valuemin={0}
        aria-valuemax={100}
      >
        <div
          className={`h-full ${isComplete ? 'bg-brand-600' : 'bg-brand-500'} transition-all`}
          style={{ width: `${pct}%` }}
        />
      </div>
    </div>
  );
}
