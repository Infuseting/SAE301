export default function Checkbox({ className = '', ...props }) {
    return (
        <input
            {...props}
            type="checkbox"
            className={
                'rounded border-gray-300 text-[#9333ea] shadow-sm focus:ring-[#9333ea] dark:border-gray-600 ' +
                className
            }
        />
    );
}
