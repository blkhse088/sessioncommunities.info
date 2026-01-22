# Server Icons

This directory contains custom server icon files for the Session Communities listing website.

## File Naming Convention

Upload server icon files using the hostname as the filename. Multiple formats are supported:

### Supported Formats (in order of preference):
1. `.png` - Preferred format
2. `.webp` - WebP format
3. `.jpg` - JPEG format  
4. `.jpeg` - JPEG format (alternative)
5. `.gif` - GIF format
6. No extension - Direct hostname match

### Examples:
```
custom/server-icons/open.getsession.org.png
custom/server-icons/community.example.com.webp
custom/server-icons/test.server.jpg
custom/server-icons/legacy.server
```

## How It Works

1. **Smart Detection**: The system automatically detects uploaded icon files
2. **Automatic Processing**: Icons are resized to 64x64 and converted to WebP
3. **Caching**: Processed icons are cached for performance
4. **Fallback**: If no custom icon exists, the system falls back to room-based icons or default colored circles

## Configuration

No configuration changes are required for basic file uploads. The system will automatically:

- Detect files uploaded to this directory
- Process them into the appropriate format
- Use them as server icons in the website

## Directory Structure

```
custom/
├── server-icons/          # Upload your icon files here
│   ├── open.getsession.org.png
│   ├── community.example.com.webp
│   └── test.server
├── config/
│   └── known-servers.ini  # Existing configuration
└── ...
```

## Processing Details

- **Input**: Any common image format
- **Output**: 64x64 WebP (optimized for web)
- **Aspect Ratio**: Maintained with centering on square canvas
- **Transparency**: Supported
- **Reprocessing**: Occurs when source file is updated

## Backward Compatibility

Existing configurations using room-based icons continue to work:
```ini
[https://open.getsession.org]
pubkey=a03c383cf63c3c4efe67acc52112a6dd734b3a946b9545f488aaa93da7991238
icon=session
```

## Testing

A test file `test.example.com` is included for development purposes.
This will be ignored in production but demonstrates the naming convention.