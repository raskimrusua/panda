import { forwardRef, type InputHTMLAttributes } from 'react';
import { cn } from '@/lib/utils';

export interface InputProps extends InputHTMLAttributes<HTMLInputElement> {
  invalid?: boolean;
}

export const Input = forwardRef<HTMLInputElement, InputProps>(function Input(
  { className, invalid, ...props },
  ref,
) {
  return (
    <input
      ref={ref}
      className={cn(
        'flex h-10 w-full rounded-md border bg-white px-3 py-2 text-sm',
        'placeholder:text-gray-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600',
        'disabled:cursor-not-allowed disabled:opacity-50',
        invalid ? 'border-danger-500' : 'border-gray-300',
        className,
      )}
      {...props}
    />
  );
});
