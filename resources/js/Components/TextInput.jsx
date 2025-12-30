import { forwardRef, useEffect, useImperativeHandle, useRef } from 'react';

export default forwardRef(function TextInput(
    { type = 'text', className = '', isFocused = false, ...props },
    ref,
) {
    const localRef = useRef(null);

    useImperativeHandle(ref, () => ({
        focus: () => localRef.current?.focus(),
    }));

    useEffect(() => {
        if (isFocused) {
            localRef.current?.focus();
        }
    }, [isFocused]);

    return (
        <input
            {...props}
            type={type}
            className={
                'rounded-md border-gray-300 bg-white text-gray-900 shadow-sm focus:border-[#9333ea] focus:ring-[#9333ea] dark:bg-[#18181b] dark:border-gray-600 dark:text-gray-100 dark:placeholder-gray-400 ' +
                className
            }
            ref={localRef}
        />
    );
});
