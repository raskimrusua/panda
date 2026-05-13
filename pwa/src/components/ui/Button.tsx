import { forwardRef, type ButtonHTMLAttributes } from 'react';
import { cn } from '@/lib/utils';

export type ButtonVariant = 'primary' | 'secondary' | 'ghost' | 'danger';
export type ButtonSize = 'sm' | 'md' | 'lg';

export interface ButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: ButtonVariant;
  size?: ButtonSize;
  loading?: boolean;
}

export const variantClass: Record<ButtonVariant, string> = {
  primary: 'bg-brand-700 hover:bg-brand-800 text-white',
  secondary: 'bg-white border border-gray-300 hover:bg-gray-50 text-gray-900',
  ghost: 'bg-transparent hover:bg-gray-100 text-gray-700',
  danger: 'bg-danger-600 hover:bg-danger-500 text-white',
};

export const sizeClass: Record<ButtonSize, string> = {
  sm: 'h-8 px-3 text-sm',
  md: 'h-10 px-4 text-sm',
  lg: 'h-12 px-6 text-base',
};

export const buttonClasses = (variant: ButtonVariant = 'primary', size: ButtonSize = 'md') =>
  cn(
    'inline-flex items-center justify-center rounded-md font-medium transition-colors',
    'focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2 focus-visible:outline-none',
    'disabled:opacity-50 disabled:cursor-not-allowed',
    variantClass[variant],
    sizeClass[size],
  );

export const Button = forwardRef<HTMLButtonElement, ButtonProps>(function Button(
  { className, variant = 'primary', size = 'md', loading, disabled, children, ...props },
  ref,
) {
  return (
    <button
      ref={ref}
      disabled={disabled ?? loading}
      className={cn(buttonClasses(variant, size), className)}
      {...props}
    >
      {loading ? <span className="opacity-70">…</span> : children}
    </button>
  );
});
