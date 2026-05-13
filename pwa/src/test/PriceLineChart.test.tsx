import { describe, it, expect } from 'vitest';
import { render } from '@testing-library/react';
import { PriceLineChart, type PriceLinePoint } from '@/components/PriceLineChart';

describe('<PriceLineChart />', () => {
  it('renders empty-state when given no points', () => {
    const { container } = render(<PriceLineChart points={[]} />);
    expect(container.textContent).toContain('No data');
  });

  it('renders an SVG with a path for the real series', () => {
    const points: PriceLinePoint[] = [
      { label: '01-01', value: 50 },
      { label: '02-01', value: 60 },
      { label: '03-01', value: 70 },
    ];
    const { container } = render(<PriceLineChart points={points} />);
    const paths = container.querySelectorAll('path');
    expect(paths.length).toBeGreaterThanOrEqual(1);
    // One solid path for history; first path should NOT have stroke-dasharray.
    expect(paths[0]?.getAttribute('stroke-dasharray')).toBeNull();
  });

  it('renders a dashed second path when forecast points are present', () => {
    const points: PriceLinePoint[] = [
      { label: '01', value: 50 },
      { label: '02', value: 60 },
      { label: 'F1', value: 65, isForecast: true },
      { label: 'F2', value: 70, isForecast: true },
    ];
    const { container } = render(<PriceLineChart points={points} />);
    const paths = container.querySelectorAll('path');
    expect(paths.length).toBe(2);
    expect(paths[1]?.getAttribute('stroke-dasharray')).toBe('6 4');
  });
});
