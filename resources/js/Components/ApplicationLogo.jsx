export default function ApplicationLogo({ big = false, ...props }) {
    return (
        <img
            {...props}
            src={big ? "/logo.svg" : "/logo_min.svg"}
            alt="Application Logo"
        />
    );
}
