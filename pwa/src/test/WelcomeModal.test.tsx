import { describe, it, expect, beforeEach } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@/i18n';
import { WelcomeModal } from '@/components/WelcomeModal';

const DISMISS_KEY = 'panda.welcome.dismissed.v1';

describe('<WelcomeModal />', () => {
  beforeEach(() => {
    localStorage.clear();
  });

  it('shows on first load when no dismissal flag is set', async () => {
    render(<WelcomeModal />);
    const dialog = await screen.findByRole('dialog');
    expect(dialog).toBeInTheDocument();
    expect(screen.getByText(/Karibu Panda/i)).toBeInTheDocument();
    // All three onboarding steps render (step1/2/3 titles in default EN)
    expect(screen.getByText(/Plan a season per crop/i)).toBeInTheDocument();
    expect(screen.getByText(/Log activities as you do them/i)).toBeInTheDocument();
    expect(screen.getByText(/Works without internet/i)).toBeInTheDocument();
  });

  it('does not show when localStorage flag is already set', async () => {
    localStorage.setItem(DISMISS_KEY, '1');
    render(<WelcomeModal />);
    // Wait past the 250ms delay; nothing should appear.
    await new Promise((r) => setTimeout(r, 350));
    expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
  });

  it('persists the dismissal flag and closes when "Got it" is clicked', async () => {
    render(<WelcomeModal />);
    await screen.findByRole('dialog');
    const cta = screen.getByRole('button', { name: /Got it/i });
    await userEvent.click(cta);
    await waitFor(() => expect(screen.queryByRole('dialog')).not.toBeInTheDocument());
    expect(localStorage.getItem(DISMISS_KEY)).toBe('1');
  });

  it('persists dismissal even when closed via the modal X', async () => {
    render(<WelcomeModal />);
    await screen.findByRole('dialog');
    await userEvent.click(screen.getByRole('button', { name: 'Close' }));
    await waitFor(() => expect(screen.queryByRole('dialog')).not.toBeInTheDocument());
    expect(localStorage.getItem(DISMISS_KEY)).toBe('1');
  });
});
