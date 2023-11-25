FROM openjdk:alpine3.8
ADD /build/App.jar backend.jar
ENTRYPOINT ["java", "-jar", "backend.jar"]