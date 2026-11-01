import React, { useState } from 'react';

/**
 * Modern, robust UserAvatar component for Edusfera PWA.
 * Renders avatar image with graceful fallback to gradient initials on load errors or missing photos.
 */
export default function UserAvatar({
  src,
  name = 'Пользователь',
  initials,
  size = 'md', // 'xs' (28px), 'sm' (36px), 'md' (44px), 'lg' (52px), 'xl' (64px)
  className = '',
  rounded = 'rounded-2xl',
  innerRounded = 'rounded-[14px]',
  bordered = true,
  alt,
}) {
  const [hasError, setHasError] = useState(false);

  const sizeClasses = {
    xs: 'w-7 h-7 text-[10px]',
    sm: 'w-9 h-9 text-xs',
    md: 'w-11 h-11 text-xs',
    lg: 'w-14 h-14 text-sm',
    xl: 'w-16 h-16 text-base font-extrabold',
  }[size] || 'w-11 h-11 text-xs';

  const computedInitials = initials || (
    name
      ? name.trim().split(/\s+/).slice(0, 2).map((p) => p[0]?.toUpperCase()).join('')
      : 'ED'
  );

  const showImage = Boolean(src && !hasError);

  if (showImage) {
    return (
      <div
        className={`relative shrink-0 overflow-hidden ${sizeClasses} ${rounded} ${
          bordered ? 'ring-2 ring-purple-500/20 dark:ring-white/10' : ''
        } ${className}`}
      >
        <img
          src={src}
          alt={alt || name}
          onError={() => setHasError(true)}
          className={`w-full h-full object-cover ${rounded}`}
          loading="lazy"
        />
      </div>
    );
  }

  // Fallback: Aurora gradient frame with dark inner block & crisp white initials
  return (
    <div
      className={`relative shrink-0 ${sizeClasses} ${rounded} ${
        bordered ? 'bg-gradient-to-tr from-[#7D39EB] to-[#C6FF33] p-[2px]' : ''
      } ${className}`}
    >
      <div
        className={`w-full h-full ${innerRounded} dark:bg-[#121214] bg-slate-900 flex items-center justify-center font-bold text-white uppercase tracking-wider select-none shadow-sm`}
      >
        {computedInitials}
      </div>
    </div>
  );
}
