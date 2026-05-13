import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@/i18n';
import { HelpTooltip } from '@/components/ui/HelpTooltip';

describe('<HelpTooltip />', () => {
  const props = { title: 'Acreage', body: 'Total area in acres.' };

  it('renders a trigger button labelled with the title', () => {
    render(<HelpTooltip {...props} />);
    expect(screen.getByRole('button', { name: 'Acreage' })).toBeInTheDocument();
  });

  it('does not render the popover until the trigger is clicked', () => {
    render(<HelpTooltip {...props} />);
    expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
  });

  it('opens the popover on click and shows title + body', async () => {
    render(<HelpTooltip {...props} />);
    await userEvent.click(screen.getByRole('button', { name: 'Acreage' }));
    const dialog = await screen.findByRole('dialog', { name: 'Acreage' });
    expect(dialog).toBeInTheDocument();
    expect(screen.getByText('Total area in acres.')).toBeInTheDocument();
  });

  it('toggles closed when the trigger is clicked again', async () => {
    render(<HelpTooltip {...props} />);
    const trigger = screen.getByRole('button', { name: 'Acreage' });
    await userEvent.click(trigger);
    expect(screen.getByRole('dialog')).toBeInTheDocument();
    await userEvent.click(trigger);
    expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
  });

  it('closes when the close (X) button is clicked', async () => {
    render(<HelpTooltip {...props} />);
    await userEvent.click(screen.getByRole('button', { name: 'Acreage' }));
    await userEvent.click(screen.getByRole('button', { name: 'Close' }));
    expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
  });

  it('closes on Escape', async () => {
    render(<HelpTooltip {...props} />);
    await userEvent.click(screen.getByRole('button', { name: 'Acreage' }));
    await userEvent.keyboard('{Escape}');
    expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
  });

  it('uses an explicit label for the trigger when provided', () => {
    render(<HelpTooltip {...props} label="Help with Acreage" />);
    expect(screen.getByRole('button', { name: 'Help with Acreage' })).toBeInTheDocument();
  });
});
